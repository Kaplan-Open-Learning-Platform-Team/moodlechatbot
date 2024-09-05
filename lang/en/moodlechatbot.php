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
 * @copyright   2024 Your Name <your@email.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'Moodle Chat Bot';
$string['modulename'] = 'Moodle Chat Bot';
$string['modulenameplural'] = 'Moodle Chat Bots';
$string['moodlechatbotname'] = 'Moodle Chat Bot Name';
$string['moodlechatbotname_help'] = 'This is the name of the Moodle Chat Bot instance.';
$string['moodlechatbotsettings'] = 'Moodle Chat Bot Settings';
$string['moodlechatbotfieldset'] = 'Custom example fieldset';
$string['pluginadministration'] = 'Moodle Chat Bot Administration';

// Chat interface strings
$string['typemessage'] = 'Type your message here...';
$string['send'] = 'Send';
$string['botresponse'] = 'Bot Response';

// Error messages
$string['errornomessage'] = 'Please enter a message.';
$string['errorsendfailed'] = 'Failed to send message. Please try again.';

// Capability strings
$string['moodlechatbot:addinstance'] = 'Add a new Moodle Chat Bot';
$string['moodlechatbot:view'] = 'View Moodle Chat Bot';
$string['moodlechatbot:interact'] = 'Interact with Moodle Chat Bot';

// Settings strings
$string['apikey'] = 'API Key';
$string['apikey_desc'] = 'Enter the API key for the chat bot service.';
$string['maxresponselength'] = 'Maximum Response Length';
$string['maxresponselength_desc'] = 'The maximum number of characters in the bot\'s response.';

// Event strings
$string['eventmessagesent'] = 'Message sent';
$string['eventresponsegenerated'] = 'Response generated';
