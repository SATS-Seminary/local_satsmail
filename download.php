<?php
/*
South African Theological Seminary
 */

use local_satsmail\exception;
use local_satsmail\message;
use local_satsmail\settings;
use local_satsmail\user;

require_once('../../config.php');
require_once("$CFG->libdir/filelib.php");

$messageid = required_param('m', PARAM_INT);

require_login(null, false);

if (!settings::is_installed()) {
    throw new moodle_exception('errorpluginnotinstalled', 'local_satsmail');
}

$user = user::current();
try {
    $message = message::get($messageid);
} catch (exception $e) {
    send_file_not_found();
}
if (!$user || !$user->can_view_files($message)) {
    send_file_not_found();
}

$context = $message->course->get_context();
$files = get_file_storage()->get_area_files(
    $context->id,
    'local_satsmail',
    'message',
    $message->id,
    'filepath, filename',
    false
);

$zipfiles = [];
foreach ($files as $file) {
    $filename = clean_filename($file->get_filepath() . $file->get_filename());
    $zipfiles[$filename] = $file;
}

$zipper = new zip_packer();
$tempzip = tempnam($CFG->tempdir . '/', 'local_satsmail_');

if ($zipper->archive_to_pathname($zipfiles, $tempzip)) {
    $filename = clean_filename($message->sender()->fullname() . ' - ' . $message->subject . '.zip');
    send_temp_file($tempzip, $filename);
} else {
    send_file_not_found();
}

