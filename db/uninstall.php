<?php
/*
South African Theological Seminary
 */

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/filelib.php');

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

