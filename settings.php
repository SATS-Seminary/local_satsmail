<?php
/*
South African Theological Seminary
 */

use local_satsmail\settings;
use local_satsmail\output\strings;

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $defaults = settings::defaults();

    $settings = new admin_settingpage('local_satsmail', strings::get('pluginname'));

    // Backup.
    $settings->add(new admin_setting_heading('local_satsmail_backup', get_string('backup'), ''));

    $name = 'local_satsmail/enablebackup';
    $visiblename = strings::get('configenablebackup');
    $description = strings::get('configenablebackupdesc');
    $defaultsetting = $defaults->enablebackup;
    $settings->add(new admin_setting_configcheckbox($name, $visiblename, $description, $defaultsetting));

    // New mail.
    $settings->add(new admin_setting_heading('local_satsmail_newmail', strings::get('newmail'), ''));

    // Number of recipients.
    $name = 'local_satsmail/maxrecipients';
    $visiblename = strings::get('configmaxrecipients');
    $description = strings::get('configmaxrecipientsdesc');
    $defaultsetting = $defaults->maxrecipients;
    $paramtype = PARAM_INT;
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, $defaultsetting, $paramtype));

    // User search limit.
    $name = 'local_satsmail/usersearchlimit';
    $visiblename = strings::get('configusersearchlimit');
    $description = strings::get('configusersearchlimitdesc');
    $defaultsetting = $defaults->usersearchlimit;
    $paramtype = PARAM_INT;
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, $defaultsetting, $paramtype));

    // Number of attachments.
    $name = 'local_satsmail/maxfiles';
    $visiblename = strings::get('configmaxattachments');
    $description = strings::get('configmaxattachmentsdesc');
    $defaultsetting = $defaults->maxfiles;
    $paramtype = PARAM_INT;
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, $defaultsetting, $paramtype));

    // Attachment size.
    $name = 'local_satsmail/maxbytes';
    $visiblename = strings::get('configmaxattachmentsize');
    $description = strings::get('configmaxattachmentsizedesc');
    $defaultsetting = $defaults->maxbytes;
    $choices = get_max_upload_sizes($CFG->maxbytes ?? 0, 0, 0, settings::get()->maxbytes);
    $settings->add(new admin_setting_configselect($name, $visiblename, $description, $defaultsetting, $choices));

    // Autosave interval.
    $name = 'local_satsmail/autosaveinterval';
    $visiblename = strings::get('configautosaveinterval');
    $description = strings::get('configautosaveintervaldesc');
    $defaultsetting = $defaults->autosaveinterval;
    $paramtype = PARAM_INT;
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, $defaultsetting, $paramtype));

    // Trays.
    $settings->add(new admin_setting_heading('local_satsmail_trays', strings::get('trays'), ''));

    // Global trays.
    $name = 'local_satsmail/globaltrays';
    $visiblename = strings::get('configglobaltrays');
    $description = strings::get('configglobaltraysdesc');
    $defaultsetting = [];
    foreach ($defaults->globaltrays as $tray) {
        $defaultsetting[$tray] = 1;
    }
    $choices = [
        'starred' => strings::get('starredplural'),
        'sent' => strings::get('sentplural'),
        'drafts' => strings::get('drafts'),
        'trash' => strings::get('trash'),
    ];
    $settings->add(new admin_setting_configmulticheckbox($name, $visiblename, $description, $defaultsetting, $choices));

    // Course trays.
    $name = 'local_satsmail/coursetrays';
    $visiblename = strings::get('configcoursetrays');
    $description = strings::get('configcoursetraysdesc');
    $defaultsetting = $defaults->coursetrays;
    $choices = [
        'none' => get_string('none'),
        'unread' => strings::get('courseswithunreadmessages'),
        'all' => get_string('allcourses', 'search'),
    ];
    $settings->add(new admin_setting_configselect($name, $visiblename, $description, $defaultsetting, $choices));

    // Course trays name.
    $name = 'local_satsmail/coursetraysname';
    $visiblename = strings::get('configcoursetraysname');
    $description = strings::get('configcoursetraysnamedesc');
    $defaultsetting = $defaults->coursetraysname;
    $choices = [
        'shortname' => get_string('shortname'),
        'fullname' => get_string('fullname'),
    ];
    $settings->add(new admin_setting_configselect($name, $visiblename, $description, $defaultsetting, $choices));

    // Filter by course.
    $name = 'local_satsmail/filterbycourse';
    $visiblename = strings::get('configfilterbycourse');
    $description = strings::get('configfilterbycoursedesc');
    $defaultsetting = $defaults->filterbycourse;
    $choices = [
        'hidden' => get_string('hide'),
        'shortname' => get_string('shortname'),
        'fullname' => get_string('fullname'),
    ];
    $settings->add(new admin_setting_configselect($name, $visiblename, $description, $defaultsetting, $choices));

    // Messages.
    $settings->add(new admin_setting_heading('local_satsmail_messages', strings::get('messages'), ''));

    // Course badge type.
    $name = 'local_satsmail/coursebadges';
    $visiblename = strings::get('configcoursebadges');
    $description = strings::get('configcoursebadgesdesc');
    $defaultsetting = $defaults->coursebadges;
    $choices = [
        'hidden' => get_string('hide'),
        'shortname' => get_string('shortname'),
        'fullname' => get_string('fullname'),
    ];
    $settings->add(new admin_setting_configselect($name, $visiblename, $description, $defaultsetting, $choices));

    // Course badge length.
    $name = 'local_satsmail/coursebadgeslength';
    $visiblename = strings::get('configcoursebadgeslength');
    $description = strings::get('configcoursebadgeslengthdesc');
    $defaultsetting = $defaults->coursebadgeslength;
    $paramtype = PARAM_INT;
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, $defaultsetting, $paramtype));

    // Search.
    $settings->add(new admin_setting_heading('local_satsmail_search', strings::get('search'), ''));

    // Incremental search.
    $name = 'local_satsmail/incrementalsearch';
    $visiblename = strings::get('configincrementalsearch');
    $description = strings::get('configincrementalsearchdesc');
    $defaultsetting = $defaults->incrementalsearch;
    $settings->add(new admin_setting_configcheckbox($name, $visiblename, $description, $defaultsetting));

    // Incremental search limit.
    $name = 'local_satsmail/incrementalsearchlimit';
    $visiblename = strings::get('configincrementalsearchlimit');
    $description = strings::get('configincrementalsearchlimitdesc');
    $defaultsetting = $defaults->incrementalsearchlimit;
    $paramtype = PARAM_INT;
    $settings->add(new admin_setting_configtext($name, $visiblename, $description, $defaultsetting, $paramtype));

    // Navigation.
    $settings->add(new admin_setting_heading('local_satsmail_navigation', strings::get('navigation'), ''));

    // Course link.
    $name = 'local_satsmail/courselink';
    $visiblename = strings::get('configcourselink');
    $description = strings::get('configcourselinkdesc');
    $defaultsetting = $defaults->courselink;
    $choices = [
        'hidden' => get_string('hide'),
        'shortname' => get_string('shortname'),
        'fullname' => get_string('fullname'),
    ];
    $settings->add(new admin_setting_configselect($name, $visiblename, $description, $defaultsetting, $choices));

    $ADMIN->add('localplugins', $settings);
}

