<?php
/*
South African Theological Seminary
 */

defined('MOODLE_INTERNAL') || die;

$messageproviders = [
    'mail' => [
        'defaults' => [
            'popup' => MESSAGE_DISALLOWED,
            'email' => MESSAGE_PERMITTED + MESSAGE_DEFAULT_ENABLED,
        ],
    ],
];

