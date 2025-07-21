<?php
/*
SATS Mail by South African Theological Seminary
 */

defined('MOODLE_INTERNAL') || die;

$observers = [
    [
        'eventname' => 'core\event\course_deleted',
        'callback'  => 'local_satsmail\observer::course_deleted',
    ],
];

