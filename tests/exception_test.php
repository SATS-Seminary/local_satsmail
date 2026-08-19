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
 * Tests for the exception functionality.
 *
 * @package    local_satsmail
 * @copyright  2026 South African Theological Seminary
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_satsmail;

/**
 * Tests for the exception class.
 *
 * @covers \local_satsmail\exception
 */
final class exception_test extends \basic_testcase {
    public function test_construct(): void {
        $exception = new exception('errortoomanyrecipients', 123, 'debug info');

        self::assertEquals('errortoomanyrecipients', $exception->errorcode);
        self::assertEquals('local_satsmail', $exception->module);
        self::assertEquals(123, $exception->a);
        self::assertEquals('', $exception->link);
        self::assertEquals('debug info', $exception->debuginfo);
    }
}
