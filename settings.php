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
    
    // Add a setting for the Groq API key.
    $settings->add(new admin_setting_configtext(
        'mod_moodlechatbot/groq_api_key',
        get_string('groq_api_key', 'mod_moodlechatbot'),
        get_string('groq_api_key_desc', 'mod_moodlechatbot'),
        '',  // default value
        PARAM_TEXT
    ));

    // Add a setting for the AI model to use.
    $settings->add(new admin_setting_configselect(
        'mod_moodlechatbot/ai_model',
        get_string('ai_model', 'mod_moodlechatbot'),
        get_string('ai_model_desc', 'mod_moodlechatbot'),
        'mixtral-8x7b-32768',  // default value
        array(
            'mixtral-8x7b-32768' => 'Mixtral 8x7B',
            'llama2-70b-4096' => 'LLaMA2 70B',
            // Add more models as needed
        )
    ));

    // Add a setting for maximum response tokens.
    $settings->add(new admin_setting_configtext(
        'mod_moodlechatbot/max_tokens',
        get_string('max_tokens', 'mod_moodlechatbot'),
        get_string('max_tokens_desc', 'mod_moodlechatbot'),
        '1000',  // default value
        PARAM_INT
    ));
}
