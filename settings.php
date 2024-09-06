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

    // API Secret setting
    $settings->add(new admin_setting_configpasswordunmask(
        'mod_moodlechatbot/apisecret',
        get_string('apisecret', 'mod_moodlechatbot'),
        get_string('apisecret_desc', 'mod_moodlechatbot'),
        '',  // default value
        PARAM_TEXT
    ));

    // Default Bot Name setting
    $settings->add(new admin_setting_configtext(
        'mod_moodlechatbot/defaultbotname',
        get_string('defaultbotname', 'mod_moodlechatbot'),
        get_string('defaultbotname_desc', 'mod_moodlechatbot'),
        get_string('defaultbotname', 'mod_moodlechatbot'),  // default value
        PARAM_TEXT
    ));

    // Default Welcome Message setting
    $settings->add(new admin_setting_configtextarea(
        'mod_moodlechatbot/defaultwelcomemessage',
        get_string('defaultwelcomemessage', 'mod_moodlechatbot'),
        get_string('defaultwelcomemessage_desc', 'mod_moodlechatbot'),
        get_string('defaultwelcomemessage', 'mod_moodlechatbot'),  // default value
        PARAM_TEXT
    ));

    // Maximum Messages setting
    $settings->add(new admin_setting_configtext(
        'mod_moodlechatbot/maxmessages',
        get_string('maxmessages', 'mod_moodlechatbot'),
        get_string('maxmessages_desc', 'mod_moodlechatbot'),
        100,  // default value
        PARAM_INT
    ));

    // Enable Logging setting
    $settings->add(new admin_setting_configcheckbox(
        'mod_moodlechatbot/enablelogging',
        get_string('enablelogging', 'mod_moodlechatbot'),
        get_string('enablelogging_desc', 'mod_moodlechatbot'),
        0  // default to disabled
    ));
}
