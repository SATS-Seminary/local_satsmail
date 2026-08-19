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
 * Language strings used by the user interface.
 *
 * @package    local_satsmail
 * @copyright  2026 South African Theological Seminary
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_satsmail\output;

/**
 * Language strings used by the user interface.
 */
class strings {
    /**
     * Returns a language string with parameters replaced.
     *
     * @param string $id The string identifier.
     * @param string|object|array $param A string, a number or an object to replace parameters with.
     * @return string The localized string.
     */
    public static function get(string $id, $param = null): string {
        return get_string($id, 'local_satsmail', $param);
    }

    /**
     * Returns all strings.
     *
     * @return string[] All localized strings.
     */
    public static function get_all(): array {
        return get_string_manager()->load_component_strings('local_satsmail', current_language());
    }

    /**
     * Returns the identifiers of all strings.
     *
     * @return string[]
     */
    public static function get_ids(): array {
        return array_keys(get_string_manager()->load_component_strings('local_satsmail', 'en'));
    }

    /**
     * Returns multiple strings.
     *
     * @param string[] $ids Identifiers.
     * @return string[] Localized strings indexed by identifier.
     */
    public static function get_many(array $ids): array {
        return (array) get_strings($ids, 'local_satsmail');
    }
}
