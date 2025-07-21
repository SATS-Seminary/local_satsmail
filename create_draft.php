<?php
/*
South African Theological Seminary
 */

require_once('../../config.php');
require_once($CFG->dirroot . '/local/satsmail/lib.php');

$courseid = required_param('c', PARAM_INT);
$recipientid = required_param('to', PARAM_INT);

require_login($courseid, false);
require_capability('local/satsmail:usemail', context_course::instance($courseid));

try {
    // Create a new draft message
    $draftid = \local_satsmail\external::create_message($courseid);
    // Update the draft to set the recipient
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
    // Redirect to the compose view
    $url = new moodle_url('/local/satsmail/view.php', ['t' => 'drafts', 'c' => $courseid, 'm' => $draftid]);
    redirect($url);
} catch (Exception $e) {
    // Fallback to drafts view if draft creation fails
    $url = new moodle_url('/local/satsmail/view.php', ['t' => 'drafts', 'c' => $courseid]);
    redirect($url);
}

