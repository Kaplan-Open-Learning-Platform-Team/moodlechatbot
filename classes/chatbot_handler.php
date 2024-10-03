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
        try {
            $this->log_debug("Chatbot handler constructor called");
            $this->groq_api_key = get_config('mod_moodlechatbot', 'groq_api_key');
            if (empty($this->groq_api_key)) {
                throw new \Exception("Groq API key is not configured");
            }
            $this->tool_manager = new tool_manager();
            $this->register_tools();
        } catch (\Exception $e) {
            $this->log_debug("Error in constructor: " . $e->getMessage());
            throw new \Exception("Initialization error: " . $e->getMessage());
        }
    }

    private function register_tools() {
        $this->log_debug("Registering tools");
        $this->tool_manager->register_tool('get_enrolled_courses', '\mod_moodlechatbot\tools\get_enrolled_courses');
    }

    public function handleQuery($message) {
        try {
            $this->log_debug("handleQuery called with message: " . $message);
            $response = ['success' => false, 'message' => '', 'debug' => []];

            if (empty($message)) {
                throw new \Exception("Empty message received");
            }

            $initial_response = $this->sendToGroq($message);
            $this->log_debug(['Initial Groq response' =>  $initial_response]);

            $decoded_response = json_decode($initial_response, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception("Failed to decode Groq response: " . json_last_error_msg());
            }

            if (!isset($decoded_response['choices'][0]['message']['content'])) {
                throw new \Exception("Unexpected response format from Groq API");
            }

            $content = $decoded_response['choices'][0]['message']['content'];
            $this->log_debug(['Groq content' => $content]);

            $tool_call = json_decode($content, true);

            if (json_last_error() === JSON_ERROR_NONE && isset($tool_call['tool_call'])) {
                $tool_name = $tool_call['tool_call']['name'];
                $tool_params = $tool_call['tool_call']['parameters'];

                $this->log_debug(['Executing tool' => $tool_name, 'With parameters' => $tool_params]);

                $tool_result = $this->tool_manager->execute_tool($tool_name, $tool_params);
                $this->log_debug(['Tool result' => $tool_result]);

                $final_response = $this->sendToGroq(json_encode([
                    'user_message' => $message,
                    'tool_result' => $tool_result
                ]));
                $this->log_debug(['Final Groq response' => $final_response]);

                $response['message'] = $this->formatResponse($final_response);
            } else {
                $this->log_debug(['No valid tool call found, returning initial response' => $content]);
                $response['message'] = $this->formatResponse($initial_response);
            }

            $response['success'] = true;

        } catch (\Exception $e) {
            $this->log_debug(['Error in handleQuery' => $e->getMessage()]);
            $response = [
                'success' => false,
                'message' => "An error occurred: " . $e->getMessage(),
                'debug' => $this->debug_log
            ];
        }

        $this->log_debug("Final response: " . json_encode($response));
        return json_encode($response);
    }

    private function sendToGroq($message) {
        try {
            $this->log_debug(['Sending to Groq' => $message]);
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
            $curl_error = curl_error($curl);
            curl_close($curl);

            if ($response === false) {
                throw new \Exception("Curl error: " . $curl_error);
            }

            return $response;
        } catch (\Exception $e) {
            throw new \Exception("Error sending to Groq: " . $e->getMessage());
        }
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
        try {
            $decoded = json_decode($response, true);
            if (!isset($decoded['choices'][0]['message']['content'])) {
                throw new \Exception("Unexpected response format from Groq API");
            }
            return $decoded['choices'][0]['message']['content'];
        } catch (\Exception $e) {
            throw new \Exception("Error formatting response: " . $e->getMessage());
        }
    }

    private function log_debug($message) {
        if (is_array($message) || is_object($message)) {
            $message = json_encode($message);
        }
        debug_to_console($message);
        $this->debug_log[] = $message;
    }
}