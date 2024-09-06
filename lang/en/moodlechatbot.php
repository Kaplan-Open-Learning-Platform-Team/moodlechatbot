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
$string['moodlechatbotname'] = 'Chat Bot Name';
$string['moodlechatbotname_help'] = 'This is the name of the chat bot activity instance that will appear on the course page.';
$string['moodlechatbotsettings'] = 'Chat Bot Settings';
$string['moodlechatbotfieldset'] = 'Custom example fieldset';

// Mod form strings
$string['botname'] = 'Bot Name';
$string['botname_help'] = 'This is the name that will be displayed as the sender of bot messages in the chat.';
$string['defaultbotname'] = 'Moodle Bot';
$string['welcomemessage'] = 'Welcome Message';
$string['welcomemessage_help'] = 'This message will be displayed when a user starts a new chat session.';
$string['defaultwelcomemessage'] = 'Hello! How can I assist you today?';
$string['maxmessages'] = 'Maximum Messages';
$string['maxmessages_help'] = 'The maximum number of messages to display in the chat history.';

// Chat interface strings
$string['typemessage'] = 'Type your message here...';
$string['send'] = 'Send';
$string['loading'] = 'Loading...';

// Error messages
$string['nomessages'] = 'No messages yet.';
$string['errorsending'] = 'Error sending message. Please try again.';

// Capability strings
$string['moodlechatbot:addinstance'] = 'Add a new Moodle Chat Bot';
$string['moodlechatbot:view'] = 'View Moodle Chat Bot';
$string['moodlechatbot:interact'] = 'Interact with Moodle Chat Bot';
$string['moodlechatbot:managemessages'] = 'Manage Moodle Chat Bot messages';

// Event strings
$string['eventmessagesent'] = 'Chat message sent';

// Admin settings
$string['apikey'] = 'API Key';
$string['apikey_desc'] = 'Enter the API key for the chat bot service.';
$string['apisecret'] = 'API Secret';
$string['apisecret_desc'] = 'Enter the API secret for the chat bot service.';

// Privacy API
$string['privacy:metadata:moodlechatbot_messages'] = 'Information about the chat messages for Moodle Chat Bot activities';
$string['privacy:metadata:moodlechatbot_messages:moodlechatbotid'] = 'The ID of the Moodle Chat Bot instance';
$string['privacy:metadata:moodlechatbot_messages:userid'] = 'The ID of the user who sent the message';
$string['privacy:metadata:moodlechatbot_messages:message'] = 'The content of the message';
$string['privacy:metadata:moodlechatbot_messages:timecreated'] = 'The timestamp of when the message was created';
