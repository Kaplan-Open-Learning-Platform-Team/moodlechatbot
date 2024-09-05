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
 * @copyright   2024 Your Name <your@email.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // API Key setting
    $settings->add(new admin_setting_configtext(
        'mod_moodlechatbot/apikey',
        get_string('apikey', 'mod_moodlechatbot'),
        get_string('apikey_desc', 'mod_moodlechatbot'),
        '',  // default value
        PARAM_TEXT
    ));

    // Maximum response length setting
    $settings->add(new admin_setting_configtext(
        'mod_moodlechatbot/maxresponselength',
        get_string('maxresponselength', 'mod_moodlechatbot'),
        get_string('maxresponselength_desc', 'mod_moodlechatbot'),
        '500',  // default value
        PARAM_INT
    ));

    // Enable logging setting
    $settings->add(new admin_setting_configcheckbox(
        'mod_moodlechatbot/enablelogging',
        get_string('enablelogging', 'mod_moodlechatbot'),
        get_string('enablelogging_desc', 'mod_moodlechatbot'),
        0  // default to disabled
    ));

    // Bot name setting
    $settings->add(new admin_setting_configtext(
        'mod_moodlechatbot/botname',
        get_string('botname', 'mod_moodlechatbot'),
        get_string('botname_desc', 'mod_moodlechatbot'),
        get_string('defaultbotname', 'mod_moodlechatbot'),
        PARAM_TEXT
    ));
}
