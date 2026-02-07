<?php
/*
South African Theological Seminary
 */

use local_satsmail\course;
use local_satsmail\exception;
use local_satsmail\external;
use local_satsmail\message;
use local_satsmail\output\strings;
use local_satsmail\settings;
use local_satsmail\user;

function local_satsmail_pluginfile(
    $course,
    $cm,
    $context,
    $filearea,
    $args,
    $forcedownload,
    array $options = []
) {
    global $SITE;

    require_login($SITE, false);

    $user = user::current();

    if (!settings::is_installed() || !$user || $filearea != 'message') {
        return false;
    }

    // Check message.
    $messageid = (int) array_shift($args);
    try {
        $message = message::get($messageid);
    } catch (exception $e) {
        return false;
    }
    if (!$user->can_view_files($message)) {
        return false;
    }

    // Get file.
    $fs = get_file_storage();
    $relativepath = implode('/', $args);
    $fullpath = "/$context->id/local_satsmail/$filearea/$messageid/$relativepath";
    $file = $fs->get_file_by_hash(sha1($fullpath));
    if (!$file || $file->is_directory()) {
        return false;
    }

    if (PHPUNIT_TEST) {
        return $file;
    }

    // @codeCoverageIgnoreStart
    send_stored_file($file, null, 0, true, $options);
    // @codeCoverageIgnoreEnd
}

/**
 * Renders the navigation bar popover.
 *
 * @param renderer_base $renderer
 * @return string The HTML
 */
function local_satsmail_render_navbar_output(\renderer_base $renderer) {
    global $COURSE, $PAGE;

    $user = user::current();

    if (!settings::is_installed() || WS_SERVER || AJAX_SCRIPT || !$user || !course::get_by_user($user)) {
        return '';
    }

    $url = new moodle_url('/local/satsmail/view.php', ['t' => 'inbox']);
    $ismailpage = $PAGE->url->compare($url, URL_MATCH_BASE);

    // Fallback link to avoid layout changes during page load.
    $mailicon = html_writer::tag('i', '', [
        'class' => 'fa fa-fw fa-envelope-o icon m-0',
        'style' => "font-size: 16px",
    ]);
    if ($ismailpage) {
        $spinnericon = html_writer::tag('i', '', [
            'class' => 'fa fa-fw fa-spinner fa-pulse text-primary',
            'style' => "font-size: 16px",
        ]);
        $spinner = html_writer::div($spinnericon, 'position-absolute', [
            'style' => 'top: 50%; right: 0; transform: translateY(-18px)',
        ]);
    } else {
        $spinner = '';
    }
    $link = html_writer::tag('a', $mailicon . $spinner, [
        'href' => $url,
        'class' => 'nav-link btn h-100 d-flex align-items-center px-2 py-0',
        'title' => strings::get('pluginname'),
    ]);
    $output = html_writer::div($link, 'popover-region', ['id' => 'local-satsmail-navbar']);

    if (!$ismailpage) {
        // Pass all data via a script tag to avoid web service requests.
        $courses = external::get_courses_raw();
        $courseid = 0;
        if (array_search($COURSE->id, array_column($courses, 'id')) !== false) {
            $courseid = (int) $COURSE->id;
        }
        $data = [
            'userid' => $user->id,
            'courseid' => $courseid,
            'settings' => (array) settings::get(),
            'strings' => strings::get_many([
                'allcourses',
                'archived',
                'bcc',
                'cc',
                'changecourse',
                'compose',
                'course',
                'drafts',
                'inbox',
                'nocoursematchestext',
                'pluginname',
                'preferences',
                'sendmail',
                'sentplural',
                'starredplural',
                'to',
                'trash',
            ]),
            'courses' => $courses,
            'labels' => external::get_labels_raw(),
        ];
        $output .= html_writer::script('window.local_satsmail_navbar_data = ' . json_encode($data));
        $renderer = $PAGE->get_renderer('local_satsmail');
        $output .= $renderer->svelte_script('src/navigation.ts');
    }

    return $output;
}

