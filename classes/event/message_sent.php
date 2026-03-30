<?php
/*
South African Theological Seminary
 */

namespace local_satsmail\event;

class message_sent extends \core\event\base {
    /**
     * Creates an event for each recipient of a message and triggers them.
     *
     * @param \local_satsmail\message $message The sent message.
     */
    public static function trigger_for_recipients(\local_satsmail\message $message): void {
        $recipients = $message->recipients();
        $recipientcount = count($recipients);
        $type = $recipientcount > 1 ? 'group' : 'personal';

        foreach ($recipients as $recipient) {
            $role = $message->role($recipient);
            self::create_for_recipient($message, $recipient, $role, $type)->trigger();
        }
    }

    /**
     * Creates an event for a specific recipient.
     *
     * @param \local_satsmail\message $message The sent message.
     * @param \local_satsmail\user $recipient The recipient user.
     * @param int $role The recipient's role (ROLE_TO, ROLE_CC, ROLE_BCC).
     * @param string $type The message type ('personal' or 'group').
     * @return \core\event\base
     */
    public static function create_for_recipient(
        \local_satsmail\message $message,
        \local_satsmail\user $recipient,
        int $role,
        string $type
    ): \core\event\base {
        global $USER;

        $rolenames = [
            \local_satsmail\message::ROLE_TO => 'to',
            \local_satsmail\message::ROLE_CC => 'cc',
            \local_satsmail\message::ROLE_BCC => 'bcc',
        ];

        return self::create([
            'userid' => $USER->id,
            'objectid' => $message->id,
            'relateduserid' => $recipient->id,
            'context' => $message->course->get_context(),
            'other' => [
                'role' => $rolenames[$role] ?? 'to',
                'type' => $type,
            ],
        ]);
    }

    /**
     * @deprecated Use trigger_for_recipients() or create_for_recipient() instead.
     */
    public static function create_from_message(\local_satsmail\message $message): \core\event\base {
        global $USER;

        return self::create([
            'userid' => $USER->id,
            'objectid' => $message->id,
            'context' => $message->course->get_context(),
        ]);
    }

    protected function init() {
        $this->data['objecttable'] = 'local_satsmail_messages';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    public static function get_name() {
        return \local_satsmail\output\strings::get('eventmessagesent');
    }

    public function get_description() {
        $desc = "The user with id '$this->userid' has sent the message with id '$this->objectid'";
        if ($this->relateduserid) {
            $role = $this->other['role'] ?? 'to';
            $type = $this->other['type'] ?? 'personal';
            $desc .= " to the user with id '$this->relateduserid' (role: $role, type: $type)";
        }
        return $desc . '.';
    }

    public static function get_objectid_mapping() {
        return ['db' => 'local_satsmail_messages', 'restore' => 'local_satsmail_message'];
    }

    public function get_url() {
        return new \moodle_url('/local/satsmail/view.php', ['t' => 'course', 'c' => $this->courseid, 'm' => $this->objectid]);
    }
}
