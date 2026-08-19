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
 * Event observers.
 *
 * @package    local_satsmail
 * @copyright  2026 South African Theological Seminary
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 namespace local_satsmail;

/**
 * Event observer for local_satsmail.
 */
class observer {
    /**
     * Triggered via course_deleted event.
     *
     * @param \core\event\course_deleted $event
     */
    public static function course_deleted(\core\event\course_deleted $event) {
        message::delete_course_data($event->get_context());
    }
}
