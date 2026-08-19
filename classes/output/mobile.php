<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Mobile app support.
 *
 * @package    local_satsmail
 * @copyright  2026 South African Theological Seminary
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_satsmail\output;

use local_satsmail\course;
use local_satsmail\settings;
use local_satsmail\user;

/**
 * Mobile app support.
 */
class mobile {
    /**
     * Returns the data of the init JavaScript of the mobile app addon.
     *
     * @return array Data of the init JavaScript.
     */
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

    /**
     * Returns the data of the main view of the mobile app addon.
     *
     * @param array $args Arguments passed to the view.
     * @return array Data of the view.
     */
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
