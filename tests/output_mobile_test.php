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
 * Tests for the output mobile functionality.
 *
 * @package    local_satsmail
 * @copyright  2026 South African Theological Seminary
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_satsmail;

/**
 * Tests for the mobile app support.
 *
 * @covers \local_satsmail\output\mobile
 */
final class output_mobile_test extends test\testcase {
    public function test_init(): void {
        global $CFG;

        $generator = self::getDataGenerator();
        $course = new course($generator->create_course());
        $user1 = new user($generator->create_user());
        $user2 = new user($generator->create_user());
        $generator->enrol_user($user1->id, $course->id);
        self::setUser($user1->id);

        // User with courses.
        self::assertEquals(
            ['javascript' => file_get_contents("$CFG->dirroot/local/satsmail/classes/output/mobile-init.js")],
            output\mobile::init(),
        );

        // User with no courses.
        self::setUser($user2->id);
        self::assertEquals(['disabled' => true], output\mobile::init());

        // Not installed.
        unset_config('version', 'local_satsmail');
        self::setUser($user1->id);
        self::assertEquals(['disabled' => true], output\mobile::init());
    }

    public function test_view(): void {
        global $CFG;

        self::assertEquals(
            [
                'templates' => [
                    [
                        'id' => 'main',
                        'html' => '<core-iframe src="' . $CFG->wwwroot . '/local/satsmail/view.php?t=inbox&m=123"></core-iframe>',
                    ],
                ],
                'javascript' => file_get_contents("$CFG->dirroot/local/satsmail/classes/output/mobile-view.js"),
            ],
            output\mobile::view(['t' => 'inbox', 'm' => 123])
        );
    }
}
