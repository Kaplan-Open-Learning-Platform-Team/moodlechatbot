<?php
// classes/external/send_message.php


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

namespace mod_moodlechatbot\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;

class send_message extends external_api {

    /**
     * Returns description of method parameters
     * @return external_function_parameters
     */
    public static function execute_parameters() {
        return new external_function_parameters([
            'message' => new external_value(PARAM_TEXT, 'The message to send to the chatbot')
        ]);
    }

    /**
     * Send a message to the chatbot and get the response
     * @param string $message The message to send
     * @return array The response from the chatbot
     */
    public static function execute($message) {
        global $CFG;
        require_once($CFG->dirroot . '/mod/moodlechatbot/classes/chatbot_handler.php');
    
        $params = self::validate_parameters(self::execute_parameters(), ['message' => $message]);
    
        try {
            $handler = new \mod_moodlechatbot\chatbot_handler();
            $response = $handler->handleQuery($params['message']);
    
            return [
                'response' => $response
            ];
        } catch (Exception $e) {
            throw new moodle_exception('error_executing_chatbot', 'mod_moodlechatbot', '', $e->getMessage());
        }
    }

    /**
     * Returns description of method result value
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure([
            'response' => new external_value(PARAM_TEXT, 'The response from the chatbot')
        ]);
    }
}
