<?php
/*
South African Theological Seminary
 */

defined('MOODLE_INTERNAL') || die();

$addons = [
    'local_satsmail' => [
        'handlers' => [
            'view' => [
                'init' => 'init',
                'method' => 'view',
            ],
        ],
        'lang' => [
            ['pluginname', 'local_satsmail'],
        ],
    ],
];

