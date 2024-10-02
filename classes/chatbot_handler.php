<?php
namespace mod_moodlechatbot;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/helper_functions.php');

class chatbot_handler {
    private $tool_manager;
    private $groq_client;

    public function __construct() {
        global $CFG;
        
        $this->tool_manager = new tool_manager();
        
        // Register your tools here
        $this->tool_manager->register_tool('get_enrolled_courses', 'mod_moodlechatbot\tools\get_enrolled_courses');
        
        // Initialize Groq client
        require_once($CFG->dirroot . '/mod/moodlechatbot/vendor/autoload.php');
        $this->groq_client = \Groq\Client::create([
            'api_key' => $CFG->groqapikey
        ]);

        debug_to_console("Chatbot handler initialized");
    }

    public function handleQuery($query) {
        try {
            debug_to_console("Processing query: " . $query);
            
            // First, try to determine if we need to use a specific tool
            $tool_result = $this->processToolQuery($query);
            if ($tool_result !== null) {
                debug_to_console("Tool execution result: " . json_encode($tool_result));
                return $this->formatResponse($tool_result);
            }

            // If no tool was used, process with Groq
            return $this->processWithGroq($query);
            
        } catch (\Exception $e) {
            debug_to_console("Error in handleQuery: " . $e->getMessage());
            
            return [
                'success' => false,
                'message' => 'An error occurred while processing your request.',
                'error' => $e->getMessage(),
                'debug' => output_debug_log()
            ];
        }
    }

    private function processToolQuery($query) {
        // Your existing logic to determine which tool to use
        if (strpos(strtolower($query), 'course') !== false) {
            debug_to_console("Using get_enrolled_courses tool");
            return $this->tool_manager->execute_tool('get_enrolled_courses');
        }
        
        return null; // No tool matched
    }

    private function processWithGroq($query) {
        debug_to_console("Processing with Groq API");
        
        try {
            $response = $this->groq_client->chat->completions->create([
                'model' => 'mixtral-8x7b-32768',
                'messages' => [
                    ['role' => 'system', 'content' => $this->getSystemPrompt()],
                    ['role' => 'user', 'content' => $query]
                ],
                'temperature' => 0.5,
                'max_tokens' => 1024,
                'top_p' => 1,
                'stream' => false
            ]);

            debug_to_console("Groq API response received");
            
            return $this->formatResponse([
                'success' => true,
                'message' => $response->choices[0]->message->content
            ]);

        } catch (\Exception $e) {
            debug_to_console("Groq API error: " . $e->getMessage());
            throw $e;
        }
    }

    private function formatResponse($result) {
        $response = [
            'success' => isset($result['success']) ? (bool)$result['success'] : false,
            'message' => isset($result['message']) ? (string)$result['message'] : 'No message provided'
        ];
    
        // Optionally add courses if they exist
        if (!empty($result['courses'])) {
            $response['courses'] = array_map(function($course) {
                return [
                    'id' => (int)$course['id'],
                    'shortname' => (string)$course['shortname'],
                    'fullname' => (string)$course['fullname']
                ];
            }, $result['courses']);
        }
    
        // Add debug information
        $debug_log = \mod_moodlechatbot\get_debug_log();
        if (!empty($debug_log)) {
            $response['debug'] = array_map('strval', $debug_log);
        }
    
        // Add error if it exists
        if (!empty($result['error'])) {
            $response['error'] = (string)$result['error'];
        }
    
        return $response;
    }

    private function getSystemPrompt() {
        return "You are a helpful assistant in a Moodle learning management system..."; // Your full system prompt here
    }
}