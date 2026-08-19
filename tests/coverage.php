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
 * Coverage information for the plugin.
 *
 * @package    local_satsmail
 * @copyright  2026 South African Theological Seminary
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

/**
 * Coverage information for the plugin.
 */
class local_satsmail_coverage extends phpunit_coverage_info {
    /** @var array Folders to include in coverage generation. */
    protected $includelistfolders = [
        'backup',
    ];

    /** @var array Files to include in coverage generation. */
    protected $includelistfiles = [
        'db/upgrade.php',
    ];

    /** @var array Folders to exclude from coverage generation. */
    protected $excludelistfolders = [
        'classes/test',
    ];
}

return new local_satsmail_coverage();
