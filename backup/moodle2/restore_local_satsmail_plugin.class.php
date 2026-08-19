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
 * Restore support for the SATS Mail plugin.
 *
 * @package    local_satsmail
 * @copyright  2026 South African Theological Seminary
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_local_satsmail_plugin extends restore_local_plugin {
    /**
     * Returns the restore structure of the plugin data of a course.
     *
     * @return array Restore structure.
     */
    protected function define_course_plugin_structure() {
        if (!get_config('local_satsmail', 'enablebackup')) {
            return [];
        }

        if (!$this->get_setting_value('users')) {
            return [];
        }

        return [
            new restore_path_element('local_satsmail_message', $this->get_pathfor('/messages/message')),
            new restore_path_element('local_satsmail_message_ref', $this->get_pathfor('/messages/message/refs/ref')),
            new restore_path_element('local_satsmail_message_user', $this->get_pathfor('/messages/message/users/user')),
            new restore_path_element('local_satsmail_message_label', $this->get_pathfor('/messages/message/labels/label')),
        ];
    }

    /**
     * Restores a message.
     *
     * @param array|object $data Data of the message.
     */
    public function process_local_satsmail_message($data) {
        global $DB;

        $record = new \stdClass();
        $record->courseid = $this->get_mappingid('course', $data['courseid']);
        $record->subject = $data['subject'];
        $record->content = $data['content'];
        $record->format = $data['format'];
        $record->attachments = $data['attachments'];
        $record->draft = $data['draft'];
        $record->time = $this->apply_date_offset($data['time']);
        $record->normalizedsubject = \local_satsmail\message::normalize_text($data['subject'], FORMAT_PLAIN);
        $record->normalizedcontent = \local_satsmail\message::normalize_text($data['content'], $data['format']);
        $newid = $DB->insert_record('local_satsmail_messages', $record);

        $this->set_mapping('local_satsmail_message', $data['id'], $newid, true);
    }

    /**
     * Restores a reference between messages.
     *
     * @param array|object $data Data of the reference.
     */
    public function process_local_satsmail_message_ref($data) {
        global $DB;

        $record = new \stdClass();
        $record->messageid = $this->get_new_parentid('local_satsmail_message');
        $record->reference = $this->get_mappingid('local_satsmail_message', $data['reference']);
        $DB->insert_record('local_satsmail_message_refs', $record);
    }

    /**
     * Restores the data of a user of a message.
     *
     * @param array|object $data Data of the user of the message.
     */
    public function process_local_satsmail_message_user($data) {
        global $DB;

        $roles = array_flip(\local_satsmail\message::role_names());

        $messageid = $this->get_new_parentid('local_satsmail_message');
        $userid = $this->get_mappingid('user', $data['userid']);
        $message = $DB->get_record('local_satsmail_messages', ['id' => $messageid], '*', MUST_EXIST);

        $record = new \stdClass();
        $record->messageid = $message->id;
        $record->courseid = $message->courseid;
        $record->draft = $message->draft;
        $record->time = $message->time;
        $record->userid = $userid;
        $record->role = isset($roles[$data['role']]) ? $roles[$data['role']] : 0;
        $record->unread = $data['unread'];
        $record->starred = $data['starred'];
        $record->deleted = $data['deleted'];
        $DB->insert_record('local_satsmail_message_users', $record);
    }

    /**
     * Restores a label of a message.
     *
     * @param array|object $data Data of the label of the message.
     */
    public function process_local_satsmail_message_label($data) {
        global $DB;

        $messageid = $this->get_new_parentid('local_satsmail_message');
        $userid = $this->get_mappingid('user', $data['userid']);
        $conditions = ['userid' => $userid, 'name' => $data['name']];
        $labelid = $DB->get_field('local_satsmail_labels', 'id', $conditions);
        $conditions = ['messageid' => $messageid, 'userid' => $userid];
        $messageuser = $DB->get_record('local_satsmail_message_users', $conditions, '*', MUST_EXIST);

        if (!$labelid) {
            $record = new \stdClass();
            $record->userid = $userid;
            $record->name = $data['name'];
            $record->color = $data['color'];
            $labelid = $DB->insert_record('local_satsmail_labels', $record);
        }

        $record = new \stdClass();
        $record->messageid = $messageid;
        $record->courseid = $messageuser->courseid;
        $record->draft = $messageuser->draft;
        $record->time = $messageuser->time;
        $record->labelid = $labelid;
        $record->role = $messageuser->role;
        $record->unread = $messageuser->unread;
        $record->starred = $messageuser->starred;
        $record->deleted = $messageuser->deleted;
        $DB->insert_record('local_satsmail_message_labels', $record);
    }

    /**
     * Remaps the IDs of the restored messages once the course has been restored.
     */
    protected function after_execute_course() {
        $this->add_related_files('local_satsmail', 'message', 'local_satsmail_message');
    }
}
