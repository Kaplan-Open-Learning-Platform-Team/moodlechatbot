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
        // Step 1: Log Query Received
        debugging('Debugging: Query received: ' . $message, DEBUG_DEVELOPER);

        // Step 2: Log Message Sent to LLM (Groq)
        debugging('Debugging: Sending message to Groq: ' . $message, DEBUG_DEVELOPER);
        $initial_response = $this->sendToGroq($message);

        // Step 3: Log Raw Response from LLM
        debugging('Debugging: Raw response from Groq: ' . $initial_response, DEBUG_DEVELOPER);

        // Step 4: Log Decoded LLM Response
        $decoded_response = json_decode($initial_response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            debugging('Error decoding JSON response: ' . json_last_error_msg(), DEBUG_DEVELOPER);
            return "I'm sorry, but I encountered an error while processing your request.";
        }
        debugging('Debugging: Decoded Groq response: ' . print_r($decoded_response, true), DEBUG_DEVELOPER);
        
        if (!isset($decoded_response['choices'][0]['message']['content'])) {
            debugging('Unexpected response structure from Groq API', DEBUG_DEVELOPER);
            return "I'm sorry, but I couldn't process your request at this time.";
        }

        $content = $decoded_response['choices'][0]['message']['content'];
        
        // Step 5: Log Tool Call Identification
        $tool_call = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($tool_call['tool_call'])) {
            $tool_name = $tool_call['tool_call']['name'];
            debugging('Debugging: Identified tool call: ' . $tool_name, DEBUG_DEVELOPER);
            
            // Step 6: Log Tool Parameters
            $tool_params = $tool_call['tool_call']['parameters'];
            debugging('Debugging: Tool parameters: ' . print_r($tool_params, true), DEBUG_DEVELOPER);
            
            try {
                // Step 7: Log Tool Instantiation
                debugging('Debugging: Instantiating tool: ' . $tool_name, DEBUG_DEVELOPER);
                $tool = $this->tool_manager->get_tool($tool_name);
                
                // Step 8: Log Tool Execution (Method Call)
                debugging('Debugging: Calling tool method: ' . $tool_name . '->execute(' . json_encode($tool_params) . ')', DEBUG_DEVELOPER);
                $tool_result = $tool->execute($tool_params);
                
                // Step 9: Log Raw Tool Output
                debugging('Debugging: Tool Output: ' . print_r($tool_result, true), DEBUG_DEVELOPER);
                
                // Prepare data to send back to Groq
                $data_for_groq = json_encode([
                    'user_message' => $message,
                    'tool_result' => $tool_result
                ]);
                
                // Send the tool result back to Groq for final response formatting
                $final_response = $this->sendToGroq($data_for_groq);
                
                // Step 10: Log Final Response Formatting
                $formatted_response = $this->formatResponse($final_response);
                debugging('Debugging: Formatted response: ' . $formatted_response, DEBUG_DEVELOPER);
                
                // Step 11: Log Final Response to User
                debugging('Debugging: Sending response to user: ' . $formatted_response, DEBUG_DEVELOPER);
                
                return $formatted_response;
            } catch (\Exception $e) {
                debugging('Error during tool execution: ' . $e->getMessage(), DEBUG_DEVELOPER);
                return "I'm sorry, but I encountered an error while processing your request.";
            }
        } else {
            // No tool call detected, return the formatted response
            debugging('Debugging: No tool call detected.', DEBUG_DEVELOPER);
            $formatted_response = $this->formatResponse($initial_response);
            debugging('Debugging: Sending response to user: ' . $formatted_response, DEBUG_DEVELOPER);
            return $formatted_response;
        }
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
        $error = curl_error($curl);
        curl_close($curl);
    
        if ($error) {
            debugging('cURL Error: ' . $error, DEBUG_DEVELOPER);
        }
    
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
        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['choices'][0]['message']['content'])) {
            $formatted = $decoded['choices'][0]['message']['content'];
        } else {
            debugging('Error decoding or accessing response content: ' . json_last_error_msg(), DEBUG_DEVELOPER);
            $formatted = "I'm sorry, but I couldn't generate a response at this time.";
        }
        return $formatted;
    }
}
