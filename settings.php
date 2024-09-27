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
 * Plugin administration pages are defined here.
 *
 * @package     mod_moodlechatbot
 * @category    admin
 * @copyright   2024 Kaplan Open Learning <kol-learning-tech@kaplan.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

<?php
defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('mod_moodlechatbot_settings', new lang_string('pluginname', 'mod_moodlechatbot'));

    $settings->add(new admin_setting_configtext(
        'moodlechatbot/groq_api_key',
        new lang_string('groqapikey', 'moodlechatbot'),
        new lang_string('groqapikeydesc', 'moodlechatbot'),
        '',
        PARAM_TEXT
    ));

    $ADMIN->add('modsettings', new admin_setting_heading('moodlechatbot_settings', new lang_string('pluginname', 'moodlechatbot'), new lang_string('pluginnamesettings', 'moodlechatbot')));
    $ADMIN->add('modsettings', $settings);
}
