<?php
namespace mod_moodlechatbot\external;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once(__DIR__ . '/../helper_functions.php');

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;
use mod_moodlechatbot\chatbot_handler;

class send_message extends external_api {
    public static function execute_parameters() {
        return new external_function_parameters([
            'message' => new external_value(PARAM_TEXT, 'The message to send to the chatbot')
        ]);
    }

    public static function execute($message) {
        $params = self::validate_parameters(self::execute_parameters(), ['message' => $message]);
        
        $handler = new chatbot_handler();
        $response = $handler->handleQuery($params['message']);
        
        return $response;
    }

    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the request was successful'),
            'message' => new external_value(PARAM_TEXT, 'The response message'),
            'courses' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Course ID'),
                    'shortname' => new external_value(PARAM_TEXT, 'Course short name'),
                    'fullname' => new external_value(PARAM_TEXT, 'Course full name')
                ]),
                'List of courses',
                VALUE_OPTIONAL
            ),
            'debug' => new external_multiple_structure(
                new external_value(PARAM_TEXT, 'Debug message'),
                'Debug information',
                VALUE_OPTIONAL
            ),
            'error' => new external_value(PARAM_TEXT, 'Error message if any', VALUE_OPTIONAL)
        ]);
    }
}