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
 * Event triggered when a draft is deleted.
 *
 * @package    local_satsmail
 * @copyright  2026 South African Theological Seminary
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_satsmail\event;

/**
 * Event triggered when a draft is deleted.
 */
class draft_deleted extends \core\event\base {
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
            'context' => \context_user::instance($USER->id),
        ]);
    }

    /**
     * Initialises the data of the event.
     */
    protected function init() {
        $this->data['objecttable'] = 'local_satsmail_messages';
        $this->data['crud'] = 'd';
        $this->data['edulevel'] = self::LEVEL_OTHER;
    }

    /**
     * Returns the localised name of the event.
     *
     * @return string Name of the event.
     */
    public static function get_name() {
        return \local_satsmail\output\strings::get('eventdraftdeleted');
    }

    /**
     * Returns a description of what happened.
     *
     * @return string Description of the event.
     */
    public function get_description() {
        return "The user with id '$this->userid' has deleted the draft with id '$this->objectid'.";
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
        return new \moodle_url('/local/satsmail/view.php', ['t' => 'drafts', 'm' => $this->objectid]);
    }
}
