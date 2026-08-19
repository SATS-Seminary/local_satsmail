<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Event triggered when a message is sent.
 *
 * @package    local_satsmail
 * @copyright  2026 South African Theological Seminary
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_satsmail\event;

/**
 * Event triggered when a message is sent.
 */
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
    /**
     * Creates the event from a message.
     *
     * @param \local_satsmail\message $message Message of the event.
     * @return \core\event\base Created event.
     */
    public static function create_from_message(\local_satsmail\message $message): \core\event\base {
        global $USER;

        return self::create([
            'userid' => $USER->id,
            'objectid' => $message->id,
            'context' => $message->course->get_context(),
        ]);
    }

    /**
     * Initialises the data of the event.
     */
    protected function init() {
        $this->data['objecttable'] = 'local_satsmail_messages';
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns the localised name of the event.
     *
     * @return string Name of the event.
     */
    public static function get_name() {
        return \local_satsmail\output\strings::get('eventmessagesent');
    }

    /**
     * Returns a description of what happened.
     *
     * @return string Description of the event.
     */
    public function get_description() {
        $desc = "The user with id '$this->userid' has sent the message with id '$this->objectid'";
        if ($this->relateduserid) {
            $role = $this->other['role'] ?? 'to';
            $type = $this->other['type'] ?? 'personal';
            $desc .= " to the user with id '$this->relateduserid' (role: $role, type: $type)";
        }
        return $desc . '.';
    }

    /**
     * Returns the mapping of the object ID used in backup and restore.
     *
     * @return array Mapping of the object ID.
     */
    public static function get_objectid_mapping() {
        return ['db' => 'local_satsmail_messages', 'restore' => 'local_satsmail_message'];
    }

    /**
     * Returns the URL related to the event.
     *
     * @return \moodle_url URL of the event.
     */
    public function get_url() {
        return new \moodle_url('/local/satsmail/view.php', ['t' => 'course', 'c' => $this->courseid, 'm' => $this->objectid]);
    }
}
