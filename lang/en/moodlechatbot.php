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
 * Plugin strings are defined here.
 *
 * @package     mod_moodlechatbot
 * @category    string
 * @copyright   2024 Kaplan Open Learning <kol-learning-tech@kaplan.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Moodle Chat Bot';
$string['modulename'] = 'Moodle Chat Bot';
$string['pluginadministration'] = 'Moodle Chat Bot Administration';
$string['modulenameplural'] = 'Moodle Chat Bots';
$string['moodlechatbotname'] = 'Moodle Chat Bot';
$string['moodlechatbotsettings'] = 'Moodle Chat Bot Settings';
$string['moodlechatbotfieldset'] = 'Chat Bot Settings';
$string['moodlechatbotname_help'] = 'Moodle Chat Bot Name Help';

// Settings strings
$string['apikey'] = 'Groq API Key';
$string['apikey_desc'] = 'Enter the API key for the Groq chat bot service. This key is used for authentication and should be kept secret.';
$string['defaultbotname'] = 'Default Bot Name';
$string['defaultbotname_desc'] = 'Enter the default name for the chat bot.';
$string['defaultwelcomemessage'] = 'Default Welcome Message';
$string['defaultwelcomemessage_desc'] = 'Enter the default welcome message for the chat bot.';
$string['maxmessages'] = 'Maximum Messages';
$string['maxmessages_desc'] = 'Enter the maximum number of messages to display in the chat history.';
$string['enablelogging'] = 'Enable Logging';
$string['enablelogging_desc'] = 'Enable or disable logging for the chat bot.';
$string['enabletools'] = 'Enable Tools';
$string['enabletools_desc'] = 'Enable or disable the use of tools by the chat bot.';

// Error messages
$string['error'] = 'An error occurred. Please try again later.';
$string['apierror'] = 'API Error (HTTP code: {$a}). Please contact the administrator.';
$string['apikeyerror'] = 'API authentication failed. Please check your Groq API key in the plugin settings.';
$string['invalidresponse'] = 'Invalid or unexpected response structure from API';
$string['invalidjson'] = 'Error decoding JSON response from API';
$string['missingcontent'] = 'Missing content in API response';

// Tool-related strings
$string['tool_result'] = 'Tool Result';
$string['course_info'] = 'Course Information';
