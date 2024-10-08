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
        debugging('Chatbot handler initialized', DEBUG_DEVELOPER);
    }

    private function register_tools() {
        $this->tool_manager->register_tool('get_enrolled_courses', '\mod_moodlechatbot\tools\get_enrolled_courses');
        debugging('Tools registered', DEBUG_DEVELOPER);
    }

    public function handleQuery($message) {
        debugging('Handling query: ' . $message, DEBUG_DEVELOPER);
        $initial_response = $this->sendToGroq($message);
        $decoded_response = json_decode($initial_response, true);
        
        debugging('Decoded response: ' . print_r($decoded_response, true), DEBUG_DEVELOPER);
        
        if (isset($decoded_response['choices'][0]['message']['content'])) {
            $content = $decoded_response['choices'][0]['message']['content'];
            $tool_call = json_decode($content, true);
            
            debugging('Parsed content: ' . print_r($tool_call, true), DEBUG_DEVELOPER);
            
            if (isset($tool_call['tool_call'])) {
                $tool_name = $tool_call['tool_call']['name'];
                $tool_params = $tool_call['tool_call']['parameters'];
                
                debugging('Executing tool: ' . $tool_name . ' with params: ' . print_r($tool_params, true), DEBUG_DEVELOPER);
                
                $tool_result = $this->tool_manager->execute_tool($tool_name, $tool_params);
                
                debugging('Tool execution result: ' . print_r($tool_result, true), DEBUG_DEVELOPER);
                
                // Send the tool result back to Groq for final response formatting
                $final_response = $this->sendToGroq(json_encode([
                    'user_message' => $message,
                    'tool_result' => $tool_result
                ]));
                
                debugging('Final response: ' . $final_response, DEBUG_DEVELOPER);
                
                return $this->formatResponse($final_response);
            }
            
            // If no tool call is detected, return the initial response
            debugging('No tool call detected, returning initial response', DEBUG_DEVELOPER);
            return $this->formatResponse($initial_response);
        }
        
        debugging('No valid response from Groq API', DEBUG_DEVELOPER);
        return "No valid response from Groq API";
    }

    private function sendToGroq($message) {
        debugging('Sending message to Groq: ' . $message, DEBUG_DEVELOPER);
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
    
        debugging('Response from Groq: ' . $response, DEBUG_DEVELOPER);
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
        $formatted = $decoded['choices'][0]['message']['content'] ?? "No response from Groq API";
        debugging('Formatted response: ' . $formatted, DEBUG_DEVELOPER);
        return $formatted;
    }
}