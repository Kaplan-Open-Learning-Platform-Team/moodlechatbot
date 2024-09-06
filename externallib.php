<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Moodle Chatbot external lib functions
 *
 * @package    mod_moodlechatbot
 * @category   external
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

class mod_moodlechatbot_external extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function send_message_parameters() {
        return new external_function_parameters(
            array(
                'chatbotid' => new external_value(PARAM_INT, 'The chat bot instance id'),
                'message' => new external_value(PARAM_TEXT, 'The message to send')
            )
        );
    }

    /**
     * Sends a message to the chatbot and returns the response
     * @param int $chatbotid The chat bot instance id
     * @param string $message The message to send
     * @return array with status and message
     */
    public static function send_message($chatbotid, $message) {
        global $DB, $USER;

        // Parameter validation
        $params = self::validate_parameters(self::send_message_parameters(),
            array('chatbotid' => $chatbotid, 'message' => $message));

        // Context validation
        $cm = get_coursemodule_from_instance('moodlechatbot', $params['chatbotid'], 0, false, MUST_EXIST);
        $context = context_module::instance($cm->id);
        self::validate_context($context);

        // Capability checking
        require_capability('mod/moodlechatbot:interact', $context);

        // Save the user's message to the database
        $messagerecord = new stdClass();
        $messagerecord->chatbotid = $params['chatbotid'];
        $messagerecord->userid = $USER->id;
        $messagerecord->message = $params['message'];
        $messagerecord->timecreated = time();
        $messagerecord->isbot = 0;
        $DB->insert_record('moodlechatbot_messages', $messagerecord);

        // TODO: Implement actual chatbot logic here
        // For now, we'll just echo the message back
        $botresponse = "You said: " . $params['message'] . ". This is a placeholder response from the chat bot.";

        // Save the bot's response to the database
        $botmessagerecord = new stdClass();
        $botmessagerecord->chatbotid = $params['chatbotid'];
        $botmessagerecord->userid = null;  // Bot message, no user id
        $botmessagerecord->message = $botresponse;
        $botmessagerecord->timecreated = time();
        $botmessagerecord->isbot = 1;
        $DB->insert_record('moodlechatbot_messages', $botmessagerecord);

        // Trigger an event for message sent
        $event = \mod_moodlechatbot\event\message_sent::create(array(
            'objectid' => $messagerecord->id,
            'context' => $context,
            'other' => array(
                'chatbotid' => $params['chatbotid'],
                'message' => $params['message']
            )
        ));
        $event->trigger();

        return array(
            'status' => 'success',
            'message' => $botresponse
        );
    }

    /**
     * Returns description of method result value
     * @return external_description
     */
    public static function send_message_returns() {
        return new external_single_structure(
            array(
                'status' => new external_value(PARAM_TEXT, 'The status of the operation'),
                'message' => new external_value(PARAM_TEXT, 'The response message')
            )
        );
    }
}
