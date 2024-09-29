
<?php
//lang/en/moodlechatbot.php


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
 * Plugin strings are defined here.
 *
 * @package     mod_moodlechatbot
 * @category    string
 * @copyright   2024 Kaplan Open Learning <kol-learning-tech@kaplan.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Moodle Chatbot'; // Standardized name
$string['modulename'] = 'Moodle Chatbot'; // Consistent with pluginname
$string['pluginadministration'] = 'Moodle Chatbot Administration'; // Consistent naming
$string['modulenameplural'] = 'Moodle Chatbots'; // Correct pluralization
$string['moodlechatbotname'] = 'Chatbot Name';  // More concise
$string['moodlechatbotsettings'] = 'Moodle Chatbot Settings'; // Main settings page title
$string['moodlechatbotfieldset'] = 'Chatbot Settings';  // Fieldset legend text
$string['moodlechatbotname_help'] = 'Enter a descriptive name for the chatbot instance.'; // Helpful description
$string['groqapikey'] = 'Groq API Key'; // For the API key setting
$string['groqapikeydesc'] = 'Enter your Groq API key here. This key is required for the chatbot to function.'; // Description for the API key setting
$string['pluginnamesettings'] = 'Moodle Chatbot Settings'; // Add this back as it was being used in settings.php


// Strings for web service descriptions:  (Highly recommended)
$string['getcoursesservice'] = 'Get enrolled courses';
$string['sendmessageservice'] = 'Send a message to the chatbot.';
$string['moodlechatbot:addinstance'] = 'Add a new Moodle Chatbot activity';
$string['moodlechatbot:use'] = 'Use Moodle Chatbot';
$string['moodlechatbot:view'] = 'View Moodle Chatbot';
$string['groq_api_key'] = 'Groq API Key';
$string['groq_api_key_desc'] = 'Enter your Groq API key here. You can obtain this from your Groq account.';
$string['error_executing_chatbot'] = 'An error occurred while processing your request: {$a}';
