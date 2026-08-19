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
 * Tests for the output strings functionality.
 *
 * @package    local_satsmail
 * @copyright  2026 South African Theological Seminary
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_satsmail;

/**
 * Tests for the strings class.
 *
 * @covers \local_satsmail\output\strings
 */
final class output_strings_test extends test\testcase {
    public function test_get(): void {
        self::assertEquals('{$a->index} of {$a->total}', output\strings::get('pagingsingle'));
        self::assertEquals('3 of 14', output\strings::get('pagingsingle', ['index' => '3', 'total' => '14']));
    }

    public function test_get_all(): void {
        self::assertEquals(self::load_strings(), output\strings::get_all());
    }

    public function test_get_ids(): void {
        $ids = array_keys(self::load_strings());
        self::assertEquals($ids, output\strings::get_ids());
    }

    public function test_get_many(): void {
        $strings = self::load_strings();
        $ids = self::random_items(array_keys($strings), 10);
        self::assertEquals(
            array_intersect_key($strings, array_combine($ids, $ids)),
            output\strings::get_many($ids)
        );
    }

    /**
     * Loads the language strings of the plugin from the language file.
     *
     * @return array Language strings indexed by identifier.
     */
    private static function load_strings(): array {
        global $CFG;

        $string = [];
        include("$CFG->dirroot/local/satsmail/lang/en/local_satsmail.php");

        return $string;
    }
}
