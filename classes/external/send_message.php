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
    
        debugging('Starting execution of send_message with message: ' . $message, DEBUG_DEVELOPER);
    
        $params = self::validate_parameters(self::execute_parameters(), ['message' => $message]);
    
        try {
            debugging('Creating chatbot_handler instance', DEBUG_DEVELOPER);
            $handler = new \mod_moodlechatbot\chatbot_handler();
            
            debugging('Calling handleQuery method', DEBUG_DEVELOPER);
            $response = $handler->handleQuery($params['message']);
    
            debugging('Received response from handleQuery: ' . print_r($response, true), DEBUG_DEVELOPER);
    
            return [
                'response' => $response
            ];
        } catch (\Exception $e) {
            debugging('Exception caught in send_message: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), DEBUG_DEVELOPER);
            throw new \moodle_exception('error_executing_chatbot', 'mod_moodlechatbot', '', $e->getMessage());
        } finally {
            debugging('Finished execution of send_message', DEBUG_DEVELOPER);
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
