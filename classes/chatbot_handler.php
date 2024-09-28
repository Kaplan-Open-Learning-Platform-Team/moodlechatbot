<?php
// classes/chatbot_handler.php

namespace mod_moodlechatbot;

defined('MOODLE_INTERNAL') || die();

class chatbot_handler {
    private $groq_api_key;
    private $groq_api_url = 'https://api.groq.com/openai/v1/chat/completions';
    private $tool_manager;

    public function __construct() {
        $this->groq_api_key = get_config('mod_moodlechatbot', 'groq_api_key');
        $this->tool_manager = new tool_manager();
        $this->register_tools();
    }

    private function register_tools() {
        $this->tool_manager->register_tool('get_enrolled_courses', '\mod_moodlechatbot\tools\get_enrolled_courses');
        // Register other tools here as needed
    }

    public function handleQuery($message) {
        global $CFG;
        require_once($CFG->dirroot . '/lib/filelib.php');
    
        debugging('Received message: ' . $message, DEBUG_DEVELOPER);
    
        $initial_response = $this->sendToGroq($message);
        debugging('Initial Groq response: ' . $initial_response, DEBUG_DEVELOPER);
    
        $decoded_response = json_decode($initial_response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            debugging('Failed to decode Groq response: ' . json_last_error_msg(), DEBUG_DEVELOPER);
            return "Error: Unable to process the response from the AI service.";
        }
    
        if (isset($decoded_response['choices'][0]['message']['content'])) {
            $content = $decoded_response['choices'][0]['message']['content'];
            debugging('Extracted content: ' . $content, DEBUG_DEVELOPER);
    
            // Try to decode as JSON, but don't require it to be JSON
            $tool_call = json_decode($content, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($tool_call['tool_call'])) {
                debugging('Detected tool call: ' . print_r($tool_call, true), DEBUG_DEVELOPER);
                $tool_name = $tool_call['tool_call']['name'];
                $tool_params = $tool_call['tool_call']['parameters'];
                
                try {
                    $tool_result = $this->tool_manager->execute_tool($tool_name, $tool_params);
                    debugging('Tool execution result: ' . print_r($tool_result, true), DEBUG_DEVELOPER);
                    
                    // Send the tool result back to Groq for final response formatting
                    $final_response = $this->sendToGroq(json_encode([
                        'user_message' => $message,
                        'tool_result' => $tool_result
                    ]));
                    
                    return $this->formatResponse($final_response);
                } catch (Exception $e) {
                    debugging('Tool execution failed: ' . $e->getMessage(), DEBUG_DEVELOPER);
                    return "Error: Unable to complete the requested operation.";
                }
            }
        }
        
        // If we didn't detect a tool call or couldn't process it, return the initial response
        return $this->formatResponse($initial_response);
    }

    private function sendToGroq($message) {
        $curl = curl_init();

        $payload = json_encode([
            'model' => 'llama-3.2-90b-text-preview',
            'messages' => [
                ['role' => 'system', 'content' => $this->getSystemPrompt()],
                ['role' => 'user', 'content' => $message]
            ]
        ]);

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->groq_api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $this->groq_api_key,
                'Content-Type: application/json'
            ],
        ]);

        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    private function getSystemPrompt() {
        return "You are a helpful assistant for a Moodle learning management system. " .
               "You have access to the following tools: " .
               "1. get_enrolled_courses: Retrieves the courses the current user is enrolled in. " .
               "If a user's query requires using a tool, respond with a JSON object containing " .
               "a 'tool_call' key with 'name' and 'parameters' subkeys. Otherwise, respond normally. " .
               "After receiving tool results, provide a natural language response to the user.";
    }

    private function formatResponse($response) {
        $decoded = json_decode($response, true);
        return $decoded['choices'][0]['message']['content'] ?? "No response from Groq API";
    }
}
