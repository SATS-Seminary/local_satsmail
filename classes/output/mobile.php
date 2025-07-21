<?php
/*
South African Theological Seminary
 */

namespace local_satsmail\output;

use local_satsmail\course;
use local_satsmail\settings;
use local_satsmail\user;

class mobile {
    public static function init() {
        global $CFG;

        $user = user::current();

        if (!settings::is_installed() || !$user || !course::get_by_user($user)) {
            return ['disabled' => true];
        }

        return [
            'javascript' => file_get_contents("$CFG->dirroot/local/satsmail/classes/output/mobile-init.js"),
        ];
    }

    public static function view(array $args) {
        global $CFG;

        $url = new \moodle_url('/local/satsmail/view.php', $args);

        return [
            'templates' => [
                [
                    'id' => 'main',
                    'html' => '<core-iframe src="' . $url->out(false) . '"></core-iframe>',
                ],
            ],
            'javascript' => file_get_contents("$CFG->dirroot/local/satsmail/classes/output/mobile-view.js"),
        ];
    }
}

