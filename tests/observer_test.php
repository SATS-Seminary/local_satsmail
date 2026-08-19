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
 * Tests for the observer functionality.
 *
 * @package    local_satsmail
 * @copyright  2026 South African Theological Seminary
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_satsmail;

/**
 * Tests for the event observers.
 *
 * @covers \local_satsmail\observer
 */
final class observer_test extends test\testcase {
    public function test_course_deleted(): void {
        [$users, $messages] = self::generate_random_data(true);
        $course = $messages[0]->course;
        $context = $course->get_context();

        $fs = get_file_storage();

        delete_course($course->id, false);

        self::assert_record_count(0, 'messages', ['courseid' => $course->id]);
        self::assert_record_count(0, 'message_users', ['courseid' => $course->id]);
        self::assert_record_count(0, 'message_labels', ['courseid' => $course->id]);
        foreach ($messages as $message) {
            if ($message->course->id == $course->id) {
                self::assert_record_count(0, 'message_refs', ['messageid' => $message->id]);
                self::assert_record_count(0, 'message_refs', ['reference' => $message->id]);
            } else {
                self::assert_message($message);
            }
        }
        self::assertEmpty($fs->get_area_files($context->id, 'local_satsmail', 'message'));
    }
}
