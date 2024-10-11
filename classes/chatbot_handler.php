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
        
        debugging('Received and decoded response from Groq: ' . print_r($decoded_response, true), DEBUG_DEVELOPER);
        
        if (isset($decoded_response['choices'][0]['message']['content'])) {
            $content = $decoded_response['choices'][0]['message']['content'];
            debugging('Attempting to parse content for tool call: ' . $content, DEBUG_DEVELOPER);
            
            $tool_call = json_decode($content, true);
            
            if (json_last_error() === JSON_ERROR_NONE) {
                debugging('Successfully parsed content as JSON', DEBUG_DEVELOPER);
                
                if (isset($tool_call['tool_call'])) {
                    $tool_name = $tool_call['tool_call']['name'];
                    $tool_params = $tool_call['tool_call']['parameters'];
                    
                    debugging('Tool call detected. Tool: ' . $tool_name . ', Parameters: ' . print_r($tool_params, true), DEBUG_DEVELOPER);
                    
                    try {
                        debugging('Attempting to get tool: ' . $tool_name, DEBUG_DEVELOPER);
                        $tool = $this->tool_manager->get_tool($tool_name);
                        
                        debugging('Executing tool: ' . $tool_name, DEBUG_DEVELOPER);
                        $tool_result = $tool->execute($tool_params);
                        
                        debugging('Tool execution completed. Raw result: ' . print_r($tool_result, true), DEBUG_DEVELOPER);
                        
                        // Prepare data to send back to Groq
                        $data_for_groq = json_encode([
                            'user_message' => $message,
                            'tool_result' => $tool_result
                        ]);
                        debugging('Data being sent back to Groq: ' . $data_for_groq, DEBUG_DEVELOPER);
                        
                        // Send the tool result back to Groq for final response formatting
                        $final_response = $this->sendToGroq($data_for_groq);
                        
                        debugging('Received final response from Groq after tool execution: ' . $final_response, DEBUG_DEVELOPER);
                        
                        $formatted_response = $this->formatResponse($final_response);
                        debugging('Sending response to user: ' . $formatted_response, DEBUG_DEVELOPER);
                        return $formatted_response;
                    } catch (\Exception $e) {
                        debugging('Error during tool execution: ' . $e->getMessage(), DEBUG_DEVELOPER);
                        return "I'm sorry, but I encountered an error while processing your request.";
                    }
                } else {
                    debugging('No tool call detected in the parsed content', DEBUG_DEVELOPER);
                }
            } else {
                debugging('Failed to parse content as JSON. Error: ' . json_last_error_msg(), DEBUG_DEVELOPER);
            }
            
            // If no tool call is detected or parsing failed, return the initial response
            debugging('Returning initial response as no tool call was processed', DEBUG_DEVELOPER);
            $formatted_response = $this->formatResponse($initial_response);
            debugging('Sending response to user: ' . $formatted_response, DEBUG_DEVELOPER);
            return $formatted_response;
        } else {
            debugging('Unexpected response structure from Groq API', DEBUG_DEVELOPER);
            return "I'm sorry, but I couldn't process your request at this time.";
        }
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
        $error = curl_error($curl);
        curl_close($curl);
    
        if ($error) {
            debugging('cURL Error: ' . $error, DEBUG_DEVELOPER);
        }
    
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
        if (json_last_error() === JSON_ERROR_NONE && isset($decoded['choices'][0]['message']['content'])) {
            $formatted = $decoded['choices'][0]['message']['content'];
        } else {
            debugging('Error decoding or accessing response content: ' . json_last_error_msg(), DEBUG_DEVELOPER);
            $formatted = "I'm sorry, but I couldn't generate a response at this time.";
        }
        debugging('Formatted response: ' . $formatted, DEBUG_DEVELOPER);
        return $formatted;
    }
}
