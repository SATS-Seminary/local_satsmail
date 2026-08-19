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
 * Page that creates a draft and redirects to it.
 *
 * @package    local_satsmail
 * @copyright  2026 South African Theological Seminary
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/satsmail/lib.php');

$courseid = required_param('c', PARAM_INT);
$recipientid = required_param('to', PARAM_INT);

require_login($courseid, false);
require_capability('local/satsmail:usemail', context_course::instance($courseid));

try {
    // Create a new draft message.
    $draftid = \local_satsmail\external::create_message($courseid);
    // Update the draft to set the recipient.
    \local_satsmail\external::update_message($draftid, [
        'courseid' => $courseid,
        'to' => [$recipientid],
        'cc' => [],
        'bcc' => [],
        'subject' => '',
        'content' => '',
        'format' => FORMAT_HTML,
        'draftitemid' => 0,
    ]);
    // Redirect to the compose view.
    $url = new moodle_url('/local/satsmail/view.php', ['t' => 'drafts', 'c' => $courseid, 'm' => $draftid]);
    redirect($url);
} catch (Exception $e) {
    // Fallback to drafts view if draft creation fails.
    $url = new moodle_url('/local/satsmail/view.php', ['t' => 'drafts', 'c' => $courseid]);
    redirect($url);
}
