<?php
/*
South African Theological Seminary
 */

defined('MOODLE_INTERNAL') || die;

$handlers = [
    [
        'classname' => '\local_satsmail\message\inbound\reply_handler',
        'defaultexpiration' => 0,
        'validateaddress' => true,
        'enabled' => false,
    ],
];
