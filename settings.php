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

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    // API Provider Selection
    $settings->add(new admin_setting_configselect(
        'mod_moodlechatbot/api_provider',
        get_string('api_provider', 'mod_moodlechatbot'),
        get_string('api_provider_desc', 'mod_moodlechatbot'),
        'groq',  // default value
        array(
            'groq' => 'Groq',
            'gemini' => 'Google Gemini'
        )
    ));

    // Groq API Key
    $settings->add(new admin_setting_configtext(
        'mod_moodlechatbot/groq_api_key',
        get_string('groq_api_key', 'mod_moodlechatbot'),
        get_string('groq_api_key_desc', 'mod_moodlechatbot'),
        '',  // default value
        PARAM_TEXT
    ));

    // Gemini API Key
    $settings->add(new admin_setting_configtext(
        'mod_moodlechatbot/gemini_api_key',
        get_string('gemini_api_key', 'mod_moodlechatbot'),
        get_string('gemini_api_key_desc', 'mod_moodlechatbot'),
        '',  // default value
        PARAM_TEXT
    ));
}
