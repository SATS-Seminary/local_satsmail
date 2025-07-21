<?php
/*
South African Theological Seminary
 */

use local_satsmail\course;
use local_satsmail\external;
use local_satsmail\output\strings;
use local_satsmail\settings;
use local_satsmail\user;

require_once('../../config.php');
require_once("$CFG->libdir/filelib.php");

global $PAGE;

$appid = optional_param('appid', '', PARAM_NOTAGS);
$applang = optional_param('applang', '', PARAM_LANG);
$appzoom = optional_param('appzoom', 0, PARAM_FLOAT);

// Use languuage from the app.
if ($appid != '' && $applang != '') {
    force_current_language($applang);
}

// Use text size from the app.
if ($appzoom > 0) {
    $PAGE->requires->js_init_code(
        'const style = document.documentElement.style;' .
        'style.setProperty("--appzoom", "' . $appzoom . '");' .
        'style.setProperty("zoom", "var(--appzoom)");'
    );
}

require_login(null, false);

if (!settings::is_installed()) {
    throw new moodle_exception('errorpluginnotinstalled', 'local_satsmail');
}

$url = new moodle_url('/local/satsmail/view.php');
$PAGE->set_url($url);
$PAGE->set_context(context_system::instance());
$PAGE->set_pagelayout($appid != '' ? 'embedded' : 'base');
$PAGE->set_title(strings::get('pluginname'));
$PAGE->requires->string_for_js('localecldr', 'langconfig');

$user = user::current();

if ($user && course::get_by_user($user)) {
    // Initial data passed via a script tag.
    $data = [
        'userid' => $user->id,
        'settings' => (array) settings::get(),
        'preferences' => external::get_preferences_raw(),
        'strings' => strings::get_all(),
        'mobile' => $appid != '',
    ];

    // Prepare script and styles before sending header.
    $renderer = $PAGE->get_renderer('local_satsmail');
    $sveltescript = $renderer->svelte_script('src/view.ts');

    // Print content.
    echo $OUTPUT->header();
    echo html_writer::div('', '', ['id' => 'local-satsmail-view']);
    echo html_writer::script('window.local_satsmail_view_data = ' . json_encode($data));
    echo $sveltescript;
    echo $OUTPUT->footer();
} else {
    // Print error.
    echo $OUTPUT->header();
    echo $OUTPUT->notification(strings::get('errornocourses'), 'warning', false);
    echo $OUTPUT->footer();
}

