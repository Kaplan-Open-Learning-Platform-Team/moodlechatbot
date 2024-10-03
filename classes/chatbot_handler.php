<?php

namespace mod_moodlechatbot;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../classes/helper_functions.php');
require_once(__DIR__ . '/tool.php');
require_once(__DIR__ . '/tools/get_enrolled_courses.php');

class chatbot_handler {
    private $groq_api_key;
    private $groq_api_url = 'https://api.groq.com/openai/v1/chat/completions';
    private $tool_manager;
    private $debug_log = [];

    public function __construct() {
        debug_to_console("Chatbot handler constructor called");
        try {
            $this->groq_api_key = get_config('mod_moodlechatbot', 'groq_api_key');
            if (empty($this->groq_api_key)) {
                throw new \Exception("Groq API key is not configured");
            }
            $this->tool_manager = new tool_manager();
            $this->register_tools();
        } catch (\Exception $e) {
            debug_to_console("Error in constructor: " . $e->getMessage());
            throw $e;
        }
    }

    public function handleQuery($message) {
        debug_to_console("Starting handleQuery with message: " . $message);
        $response = ['success' => false, 'message' => '', 'debug' => []];

        try {
            if (empty($message)) {
                throw new \Exception("Empty message received");
            }

            $initial_response = $this->sendToGroq($message);
            
            if (empty($initial_response)) {
                throw new \Exception("Empty response from Groq API");
            }
            
            debug_to_console("Raw Groq response: " . $initial_response);

            $decoded_response = json_decode($initial_response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Failed to decode Groq response: " . json_last_error_msg());
            }

            debug_to_console("Decoded Groq response: " . print_r($decoded_response, true));

            if (!isset($decoded_response['choices'][0]['message']['content'])) {
                throw new \Exception("Unexpected response format from Groq API");
            }

            $content = $decoded_response['choices'][0]['message']['content'];
            debug_to_console("Content from Groq: " . $content);

            // Process the content and generate response
            $response['message'] = $this->processContent($content, $message);
            $response['success'] = true;

        } catch (\Exception $e) {
            $error_message = "Error processing query: " . $e->getMessage();
            debug_to_console($error_message);
            debug_to_console("Stack trace: " . $e->getTraceAsString());
            $response['message'] = $error_message;
            $response['success'] = false;
        }

        $response['debug'] = $this->debug_log;
        
        debug_to_console("Final response object: " . print_r($response, true));
        return json_encode($response);
    }

    private function processContent($content, $original_message) {
        try {
            $tool_call = json_decode($content, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($tool_call['tool_call'])) {
                debug_to_console("Processing tool call: " . print_r($tool_call, true));
                
                $tool_name = $tool_call['tool_call']['name'];
                $tool_params = $tool_call['tool_call']['parameters'];

                $tool_result = $this->tool_manager->execute_tool($tool_name, $tool_params);
                debug_to_console("Tool execution result: " . print_r($tool_result, true));

                $final_response = $this->sendToGroq(json_encode([
                    'user_message' => $original_message,
                    'tool_result' => $tool_result
                ]));

                return $this->formatResponse($final_response);
            } else {
                debug_to_console("No tool call found, returning formatted content");
                return $content;  // Return the content directly if no tool call
            }
        } catch (\Exception $e) {
            debug_to_console("Error in processContent: " . $e->getMessage());
            throw $e;  // Re-throw the exception to be caught by handleQuery
        }
    }

    private function sendToGroq($message) {
        debug_to_console("Preparing to send to Groq: " . $message);
        
        if (empty($this->groq_api_key)) {
            throw new \Exception("Groq API key is not set");
        }

        $curl = curl_init();
        
        $payload = json_encode([
            'model' => 'llama-3.2-90b-text-preview',
            'messages' => [
                ['role' => 'system', 'content' => $this->getSystemPrompt()],
                ['role' => 'user', 'content' => $message]
            ]
        ]);

        debug_to_console("Payload to Groq: " . $payload);

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
        $curl_error = curl_error($curl);
        $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        
        curl_close($curl);

        if ($response === false) {
            throw new \Exception("Curl error: " . $curl_error);
        }

        if ($http_code !== 200) {
            debug_to_console("HTTP Error: " . $http_code . ", Response: " . $response);
            throw new \Exception("HTTP Error: " . $http_code);
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
        if (!isset($decoded['choices'][0]['message']['content'])) {
            throw new \Exception("Unexpected response format from Groq API");
        }
        return $decoded['choices'][0]['message']['content'];
    }

    private function log_debug($message) {
        if (is_array($message) || is_object($message)) {
            $message = json_encode($message);
        }
        debug_to_console($message);  // Use the helper function directly
        $this->debug_log[] = $message;  // Still store in class property if needed
    }

    private function cleanJsonResponse($response) {
        return $response; 
    }
}