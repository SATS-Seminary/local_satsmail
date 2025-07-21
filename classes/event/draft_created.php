<?php
/*
South African Theological Seminary
 */

namespace local_satsmail\event;

class draft_created extends \core\event\base {
    public static function create_from_message(\local_satsmail\message $message): \core\event\base {
        global $USER;

        return self::create([
            'userid' => $USER->id,
            'objectid' => $message->id,
            'context' => \context_user::instance($USER->id),
        ]);
    }

    protected function init() {
        $this->data['objecttable'] = 'local_satsmail_messages';
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    public static function get_name() {
        return \local_satsmail\output\strings::get('eventdraftcreated');
    }

    public function get_description() {
        return "The user with id '$this->userid' has created the draft with id '$this->objectid'.";
    }

    public static function get_objectid_mapping() {
        return ['db' => 'local_satsmail_messages', 'restore' => 'local_satsmail_message'];
    }

    public function get_url() {
        return new \moodle_url('/local/satsmail/view.php', ['t' => 'drafts', 'm' => $this->objectid]);
    }
}

