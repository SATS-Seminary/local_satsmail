<?php
/*
South African Theological Seminary
 */

class restore_local_satsmail_plugin extends restore_local_plugin {
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

    public function process_local_satsmail_message_ref($data) {
        global $DB;

        $record = new \stdClass();
        $record->messageid = $this->get_new_parentid('local_satsmail_message');
        $record->reference = $this->get_mappingid('local_satsmail_message', $data['reference']);
        $DB->insert_record('local_satsmail_message_refs', $record);
    }

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

    protected function after_execute_course() {
        $this->add_related_files('local_satsmail', 'message', 'local_satsmail_message');
    }
}

