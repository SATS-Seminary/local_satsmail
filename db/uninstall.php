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
 * Uninstallation code.
 *
 * @package    local_satsmail
 * @copyright  2026 South African Theological Seminary
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/filelib.php');

/**
 * Uninstalls the plugin.
 *
 * @return bool True on success.
 */
function xmldb_local_satsmail_uninstall() {
    global $DB;

    $fs = get_file_storage();

    $conditions = ['contextlevel' => CONTEXT_COURSE];
    $records = $DB->get_records('context', $conditions, '', 'id');

    foreach ($records as $record) {
        $fs->delete_area_files($record->id, 'local_satsmail');
    }

    return true;
}
