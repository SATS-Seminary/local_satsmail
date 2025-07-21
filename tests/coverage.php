<?php
/*
South African Theological Seminary
 */

defined('MOODLE_INTERNAL') || die();

class local_satsmail_coverage extends phpunit_coverage_info {
    protected $includelistfolders = [
        'backup',
    ];

    protected $includelistfiles = [
        'db/upgrade.php',
    ];

    protected $excludelistfolders = [
        'classes/test',
    ];
}

return new local_satsmail_coverage();

