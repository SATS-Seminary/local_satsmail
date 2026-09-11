<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tests for access to SATS Recorder recordings embedded in messages.
 *
 * @package    local_satsmail
 * @copyright  2026 South African Theological Seminary
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_satsmail;

/**
 * Tests for access to SATS Recorder recordings embedded in messages.
 *
 * @covers \local_satsmail\recording_access
 * @covers \local_satsmail\hook_callbacks
 */
final class recording_access_test extends test\testcase {
    /** @var course Course. */
    private course $course;

    /** @var user Sender, who made the recording. */
    private user $owner;

    /** @var user Recipient in "to". */
    private user $to;

    /** @var user Recipient in "cc". */
    private user $cc;

    /** @var user Recipient in "bcc". */
    private user $bcc;

    /** @var user Enrolled in the course but not a recipient. */
    private user $other;

    /** @var string A recording key in the format tiny_satsrecorder issues. */
    private string $key;

    /**
     * Sets up the test case.
     */
    public function setUp(): void {
        parent::setUp();
        $generator = self::getDataGenerator();
        $this->course = new course($generator->create_course());
        $this->owner = new user($generator->create_user());
        $this->to = new user($generator->create_user());
        $this->cc = new user($generator->create_user());
        $this->bcc = new user($generator->create_user());
        $this->other = new user($generator->create_user());
        foreach ([$this->owner, $this->to, $this->cc, $this->bcc, $this->other] as $user) {
            $generator->enrol_user($user->id, $this->course->id);
        }
        $this->key = "recordings/{$this->owner->id}/0123456789abcdef0123456789abcdef-clip.webm";
    }

    /**
     * Returns message content embedding a recording as the TinyMCE plugin does.
     *
     * @param string $key Recording key.
     * @return string
     */
    private static function embed(string $key): string {
        $url = 'https://example.com/lib/editor/tiny/plugins/satsrecorder/serve.php?key=' . rawurlencode($key) . '&amp;ctx=1';
        return '<p>Listen:</p><video controls="true"><source src="' . $url . '"></video>';
    }

    /**
     * Creates a message from the owner to the three recipients.
     *
     * @param string $content Message content.
     * @param bool $send Whether to send it or leave it as a draft.
     * @return message
     */
    private function message_from_owner(string $content, bool $send = true): message {
        $data = message_data::new($this->course, $this->owner);
        $data->subject = 'Recording';
        $data->content = $content;
        $data->format = FORMAT_HTML;
        $data->to = [$this->to];
        $data->cc = [$this->cc];
        $data->bcc = [$this->bcc];
        $message = message::create($data);
        if ($send) {
            $message->send(make_timestamp(2026, 9, 11, 12, 0));
        }
        return $message;
    }

    public function test_recipients_can_view(): void {
        $this->message_from_owner(self::embed($this->key));

        self::assertTrue(recording_access::user_can_view($this->to->id, $this->owner->id, $this->key));
        self::assertTrue(recording_access::user_can_view($this->cc->id, $this->owner->id, $this->key));
        self::assertTrue(recording_access::user_can_view($this->bcc->id, $this->owner->id, $this->key));
    }

    public function test_non_recipient_cannot_view(): void {
        $this->message_from_owner(self::embed($this->key));

        self::assertFalse(recording_access::user_can_view($this->other->id, $this->owner->id, $this->key));
    }

    public function test_draft_does_not_grant(): void {
        $this->message_from_owner(self::embed($this->key), false);

        self::assertFalse(recording_access::user_can_view($this->to->id, $this->owner->id, $this->key));
    }

    public function test_message_from_someone_else_does_not_grant(): void {
        // The pasted-key case: another user embeds a key they have seen in a
        // message of their own, hoping it grants access to its recipients.
        $data = message_data::new($this->course, $this->other);
        $data->subject = 'Not mine';
        $data->content = self::embed($this->key);
        $data->format = FORMAT_HTML;
        $data->to = [$this->to];
        message::create($data)->send(make_timestamp(2026, 9, 11, 12, 0));

        self::assertFalse(recording_access::user_can_view($this->to->id, $this->owner->id, $this->key));
    }

    public function test_forward_recipient_can_view(): void {
        $message = $this->message_from_owner(self::embed($this->key));

        $data = message_data::forward($message, $this->to);
        $data->to = [$this->other];
        message::create($data)->send(make_timestamp(2026, 9, 11, 13, 0));

        self::assertTrue(recording_access::user_can_view($this->other->id, $this->owner->id, $this->key));
    }

    public function test_forward_of_a_forward_recipient_can_view(): void {
        $generator = self::getDataGenerator();
        $last = new user($generator->create_user());
        $generator->enrol_user($last->id, $this->course->id);
        $message = $this->message_from_owner(self::embed($this->key));

        $data = message_data::forward($message, $this->to);
        $data->to = [$this->other];
        $forward = message::create($data);
        $forward->send(make_timestamp(2026, 9, 11, 13, 0));

        self::assertFalse(recording_access::user_can_view($last->id, $this->owner->id, $this->key));

        $data = message_data::forward($forward, $this->other);
        $data->to = [$last];
        message::create($data)->send(make_timestamp(2026, 9, 11, 14, 0));

        self::assertTrue(recording_access::user_can_view($last->id, $this->owner->id, $this->key));
    }

    public function test_deleted_forever_does_not_grant(): void {
        $message = $this->message_from_owner(self::embed($this->key));

        $message->set_deleted($this->to, message::DELETED_FOREVER);

        self::assertFalse(recording_access::user_can_view($this->to->id, $this->owner->id, $this->key));
        self::assertTrue(recording_access::user_can_view($this->cc->id, $this->owner->id, $this->key));
    }

    public function test_only_the_exact_key_matches(): void {
        $this->message_from_owner(self::embed($this->key . '.extra'));

        self::assertFalse(recording_access::user_can_view($this->to->id, $this->owner->id, $this->key));
        self::assertTrue(recording_access::user_can_view($this->to->id, $this->owner->id, $this->key . '.extra'));
    }

    public function test_unencoded_key_matches(): void {
        $content = '<video><source src="/lib/editor/tiny/plugins/satsrecorder/serve.php?key=' . $this->key . '"></video>';
        $this->message_from_owner($content);

        self::assertTrue(recording_access::user_can_view($this->to->id, $this->owner->id, $this->key));
    }

    public function test_hook_grants_recipients_only(): void {
        if (!class_exists(\tiny_satsrecorder\hook\recording_access::class)) {
            $this->markTestSkipped('tiny_satsrecorder is not installed.');
        }
        $this->message_from_owner(self::embed($this->key));
        $context = \context_system::instance();
        $manager = \core\di::get(\core\hook\manager::class);

        $hook = new \tiny_satsrecorder\hook\recording_access($this->key, $this->owner->id, $context, $this->to->id);
        $manager->dispatch($hook);
        self::assertTrue($hook->is_granted());

        $hook = new \tiny_satsrecorder\hook\recording_access($this->key, $this->owner->id, $context, $this->other->id);
        $manager->dispatch($hook);
        self::assertFalse($hook->is_granted());
    }
}
