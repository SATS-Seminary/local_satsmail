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
 * Exception thrown by the plugin.
 *
 * @package    local_satsmail
 * @copyright  2026 South African Theological Seminary
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_satsmail;

/**
 * Exception thrown by the plugin.
 */
class exception extends \moodle_exception {
    /**
     * Constructor.
     *
     * @param string $errorcode Language string name.
     * @param mixed $a Language string parameters.
     * @param ?string $debuginfo Optional debugging information
     */
    public function __construct(string $errorcode, $a = null, ?string $debuginfo = null) {
        parent::__construct($errorcode, 'local_satsmail', '', $a, $debuginfo);
    }
}
