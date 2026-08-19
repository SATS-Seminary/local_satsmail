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
 * Tests for the settings functionality.
 *
 * @package    local_satsmail
 * @copyright  2026 South African Theological Seminary
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace local_satsmail;

/**
 * Tests for the settings class.
 *
 * @covers \local_satsmail\settings
 */
final class settings_test extends test\testcase {
    public function test_defaults(): void {
        set_config('maxbytes', 123000);

        $settings = settings::defaults();

        self::assertTrue($settings->enablebackup);
        self::assertEquals(100, $settings->maxrecipients);
        self::assertEquals(100, $settings->usersearchlimit);
        self::assertEquals(20, $settings->maxfiles);
        self::assertEquals(123000, $settings->maxbytes);
        self::assertEquals(5, $settings->autosaveinterval);
        self::assertEquals(['starred', 'sent', 'drafts', 'archive', 'trash'], $settings->globaltrays);
        self::assertEquals('none', $settings->coursetrays);
        self::assertEquals('fullname', $settings->coursetraysname);
        self::assertEquals('fullname', $settings->coursebadges);
        self::assertEquals(20, $settings->coursebadgeslength);
        self::assertEquals('fullname', $settings->filterbycourse);
        self::assertTrue($settings->incrementalsearch);
        self::assertEquals(1000, $settings->incrementalsearchlimit);
        self::assertEquals('hidden', $settings->courselink);
    }

    public function test_get(): void {
        set_config('maxbytes', 123000);
        set_config('enablebackup', '0', 'local_satsmail');
        set_config('maxrecipients', '20', 'local_satsmail');
        set_config('usersearchlimit', '50', 'local_satsmail');
        set_config('maxfiles', '5', 'local_satsmail');
        set_config('maxbytes', '45000', 'local_satsmail');
        set_config('autosaveinterval', '3', 'local_satsmail');
        set_config('globaltrays', 'sent,trash', 'local_satsmail');
        set_config('coursetrays', 'unread', 'local_satsmail');
        set_config('coursetraysname', 'shortname', 'local_satsmail');
        set_config('coursebadges', 'hidden', 'local_satsmail');
        set_config('coursebadgeslength', '10', 'local_satsmail');
        set_config('filterbycourse', 'hidden', 'local_satsmail');
        set_config('incrementalsearch', '0', 'local_satsmail');
        set_config('incrementalsearchlimit', '2000', 'local_satsmail');
        set_config('courselink', 'fullname', 'local_satsmail');
        \core_plugin_manager::resolve_plugininfo_class('message')::enable_plugin('airnotifier', true);
        set_config('airnotifieraccesskey', random_string());
        set_config('message_provider_local_satsmail_mail_enabled', 'email,airnotifier', 'message');
        set_config('email_provider_local_satsmail_mail_locked', '1', 'message');
        set_config('popup_provider_local_satsmail_mail_locked', '1', 'message');
        $settings = settings::get();

        self::assertFalse($settings->enablebackup);
        self::assertEquals(20, $settings->maxrecipients);
        self::assertEquals(50, $settings->usersearchlimit);
        self::assertEquals(5, $settings->maxfiles);
        self::assertEquals(45000, $settings->maxbytes);
        self::assertEquals(3, $settings->autosaveinterval);
        self::assertEquals(['sent', 'trash'], $settings->globaltrays);
        self::assertEquals('unread', $settings->coursetrays);
        self::assertEquals('shortname', $settings->coursetraysname);
        self::assertEquals('hidden', $settings->coursebadges);
        self::assertEquals(10, $settings->coursebadgeslength);
        self::assertEquals('hidden', $settings->filterbycourse);
        self::assertFalse($settings->incrementalsearch);
        self::assertEquals(2000, $settings->incrementalsearchlimit);
        self::assertEquals('fullname', $settings->courselink);
        self::assertEquals([
            [
                'name' => 'email',
                'displayname' => get_string('pluginname', 'message_email'),
                'locked' => true,
                'enabled' => true,
            ],
            [
                'name' => 'airnotifier',
                'displayname' => get_string('pluginname', 'message_airnotifier'),
                'locked' => false,
                'enabled' => true,
            ],
        ], $settings->messageprocessors);

        // Empty global trays.

        set_config('globaltrays', '', 'local_satsmail');
        $settings = settings::get();
        self::assertEquals([], $settings->globaltrays);
    }

    public function test_is_installed(): void {
        self::assertTrue(settings::is_installed());

        set_config('version', 123, 'local_satsmail');

        self::assertFalse(settings::is_installed());
    }

    public function test_text_editors(): void {
        global $CFG, $USER;

        // No setting.
        unset_config('texteditors');
        self::assertEquals([], settings::text_editors());

        // Supported editors.
        set_config('texteditors', 'atto,tiny,textarea');
        self::assertEquals(['atto', 'tiny', 'textarea'], settings::text_editors());

        // Legacy TinyMCE editor.
        set_config('texteditors', 'tinymce,atto');
        self::assertEquals(['tiny', 'atto'], settings::text_editors());

        // Unknown editor.
        set_config('texteditors', 'tiny,unknown,textarea');
        self::assertEquals(['tiny', 'textarea'], settings::text_editors());

        // User preference.
        set_config('texteditors', 'atto,tiny,textarea');
        set_user_preference('htmleditor', 'tiny', $USER);
        self::assertEquals(['tiny', 'atto', 'textarea'], settings::text_editors());
    }
}
