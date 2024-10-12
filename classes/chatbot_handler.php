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
        debugging('Debugging: Query received: ' . $message, DEBUG_DEVELOPER);
        debugging('Debugging: Sending message to Groq: ' . $message, DEBUG_DEVELOPER);
        
        $initial_response = $this->sendToGroq($message);
        
        if ($initial_response === false) {
            debugging('Error: Failed to get a response from Groq API', DEBUG_DEVELOPER);
            return "I'm sorry, but I encountered an error while communicating with the AI service.";
        }
        
        debugging('Debugging: Raw response from Groq: ' . $initial_response, DEBUG_DEVELOPER);

        $decoded_response = json_decode($initial_response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            debugging('Error decoding JSON response: ' . json_last_error_msg(), DEBUG_DEVELOPER);
            return "I'm sorry, but I encountered an error while processing the AI service response.";
        }
        debugging('Debugging: Decoded Groq response: ' . print_r($decoded_response, true), DEBUG_DEVELOPER);
        
        if (!isset($decoded_response['choices'][0]['message']['content'])) {
            debugging('Unexpected response structure from Groq API', DEBUG_DEVELOPER);
            return "I'm sorry, but I couldn't process your request at this time due to an unexpected response format.";
        }

        $content = $decoded_response['choices'][0]['message']['content'];
        
        // Try to extract tool call from the content
        $tool_call = $this->extractToolCall($content);
        
        if ($tool_call) {
            debugging('Debugging: Extracted tool call: ' . print_r($tool_call, true), DEBUG_DEVELOPER);
            
            try {
                $tool = $this->tool_manager->get_tool($tool_call['name']);
                debugging('Debugging: Calling tool method: ' . $tool_call['name'] . '->execute(' . json_encode($tool_call['parameters']) . ')', DEBUG_DEVELOPER);
                $tool_result = $tool->execute($tool_call['parameters']);
                debugging('Debugging: Tool Output: ' . print_r($tool_result, true), DEBUG_DEVELOPER);
                
                // Prepare data to send back to Groq
                $data_for_groq = json_encode([
                    'user_message' => $message,
                    'tool_result' => $tool_result
                ]);
                
                // Send the tool result back to Groq for final response formatting
                $final_response = $this->sendToGroq($data_for_groq);
                if ($final_response === false) {
                    debugging('Error: Failed to get a final response from Groq API', DEBUG_DEVELOPER);
                    return "I'm sorry, but I encountered an error while processing the tool results.";
                }
                $formatted_response = $this->formatResponse($final_response);
            } catch (\Exception $e) {
                debugging('Error during tool execution: ' . $e->getMessage(), DEBUG_DEVELOPER);
                return "I'm sorry, but I encountered an error while processing your request with the specified tool.";
            }
        } else {
            debugging('Debugging: No tool call detected or extracted.', DEBUG_DEVELOPER);
            $formatted_response = $this->formatResponse($initial_response);
        }

        debugging('Debugging: Sending response to user: ' . $formatted_response, DEBUG_DEVELOPER);
        return $formatted_response;
    }

    private function extractToolCall($content) {
        debugging('Debugging: Attempting to extract tool call from: ' . $content, DEBUG_DEVELOPER);
        
        // First, try to find a JSON block within the content
        if (preg_match('/```(?:json)?\s*(.*?)\s*```/s', $content, $matches)) {
            $json_block = $matches[1];
            $parsed_json = json_decode($json_block, true);
            if (json_last_error() === JSON_ERROR_NONE && isset($parsed_json['tool_call'])) {
                debugging('Debugging: Extracted tool call from JSON block', DEBUG_DEVELOPER);
                return $parsed_json['tool_call'];
            }
        }

        // If that fails, try to parse the entire content as JSON
        $json_content = json_decode($content, true);
        if (json_last_error() === JSON_ERROR_NONE && isset($json_content['tool_call'])) {
            debugging('Debugging: Extracted tool call from full content JSON', DEBUG_DEVELOPER);
            return $json_content['tool_call'];
        }

        // If no JSON found, try to extract tool information from natural language
        if (preg_match('/call to the \'(.*?)\' tool/i', $content, $matches)) {
            $tool_name = $matches[1];
            // Extract parameters if present, otherwise use empty array
            $parameters = [];
            if (preg_match('/parameters:\s*(\{.*?\})/s', $content, $param_matches)) {
                $parameters = json_decode($param_matches[1], true) ?: [];
            }
            debugging('Debugging: Extracted tool call from natural language', DEBUG_DEVELOPER);
            return [
                'name' => $tool_name,
                'parameters' => $parameters
            ];
        }

        debugging('Debugging: Failed to extract tool call', DEBUG_DEVELOPER);
        return null;
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
        $err = curl_error($curl);
        $info = curl_getinfo($curl);
        curl_close($curl);
    
        if ($err) {
            debugging('cURL Error: ' . $err, DEBUG_DEVELOPER);
            return false;
        }
    
        if ($info['http_code'] != 200) {
            debugging('HTTP Error: ' . $info['http_code'] . ' - Response: ' . $response, DEBUG_DEVELOPER);
            return false;
        }
    
        if (empty($response)) {
            debugging('Error: Empty response from Groq API', DEBUG_DEVELOPER);
            return false;
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
