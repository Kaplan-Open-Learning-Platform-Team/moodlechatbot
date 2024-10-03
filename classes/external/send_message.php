<?php

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