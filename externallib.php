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
            array('message' => new external_value(PARAM_TEXT, 'The message sent by the user'))
        );
    }

    /**
     * Send a message and get a response
     * 
     * @param string $message The message sent by the user
     * @return array The response from the chatbot
     */
    public static function send_message($message) {
        global $USER;

        $params = self::validate_parameters(self::send_message_parameters(), array('message' => $message));

        // Here you would typically integrate with Groq or any AI service
        // For this example, we'll just echo back a simple response
        $response = "You said: " . $params['message'] . ". This is a placeholder response from the chat bot.";

        return array(
            'status' => 'success',
            'message' => $response
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
