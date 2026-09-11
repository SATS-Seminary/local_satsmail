<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Hook callbacks.
 *
 * @package    local_satsmail
 * @copyright  2026 South African Theological Seminary
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_satsmail;

/**
 * Hook callbacks.
 */
class hook_callbacks {
    /**
     * Let message recipients play SATS Recorder recordings sent to them.
     *
     * Only dispatched when tiny_satsrecorder is installed; the hook class is
     * never resolved otherwise.
     *
     * @param \tiny_satsrecorder\hook\recording_access $hook
     */
    public static function satsrecorder_recording_access(\tiny_satsrecorder\hook\recording_access $hook): void {
        if (recording_access::user_can_view($hook->userid, $hook->ownerid, $hook->s3key)) {
            $hook->grant();
        }
    }
}
