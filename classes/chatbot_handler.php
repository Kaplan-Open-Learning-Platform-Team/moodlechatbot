<?php
// classes/chatbot_handler.php

namespace mod_moodlechatbot;

defined('MOODLE_INTERNAL') || die();

class chatbot_handler {
    private $groq_api_key;
    private $groq_api_url = 'https://api.groq.com/openai/v1/chat/completions';
    private $tool_manager;
    private $debug_log = []; // Add debug log array

    public function __construct() {
        $this->groq_api_key = get_config('mod_moodlechatbot', 'groq_api_key');
        $this->tool_manager = new tool_manager();
        $this->register_tools();
    }

    private function register_tools() {
        $this->tool_manager->register_tool('get_enrolled_courses', '\mod_moodlechatbot\tools\get_enrolled_courses');
        // Register other tools here as needed
    }

    private function log_debug($message) {
        if (is_array($message) || is_object($message)) {
            $message = json_encode($message);
        }
        debug_to_console($message);
        $this->debug_log[] = $message;
    }


    public function handleQuery($message) {
        debug_to_console("handleQuery called with message: " . $message);
        $this->log_debug("handleQuery called with message: " . $message);
        $response = ['success' => false, 'message' => '', 'debug' => []];

        try {
            $initial_response = $this->sendToGroq($message);
            $this->log_debug(['Initial Groq response' =>  $initial_response]);
            $decoded_response = json_decode($initial_response, true);

            if (isset($decoded_response['choices'][0]['message']['content'])) {
                $content = $decoded_response['choices'][0]['message']['content'];
                $tool_call = json_decode($content, true);

                if (isset($tool_call['tool_call'])) {
                    $tool_name = $tool_call['tool_call']['name'];
                    $tool_params = $tool_call['tool_call']['parameters'];

                    $tool_result = $this->tool_manager->execute_tool($tool_name, $tool_params);
                    $this->log_debug(['Tool Result' => $tool_result]);

                    // Send the tool result back to Groq for final response formatting
                    $final_response = $this->sendToGroq(json_encode([
                        'user_message' => $message,
                        'tool_result' => $tool_result
                    ]));
                    $this->log_debug(['Final Groq response' => $final_response]);


                    $response['message'] = $this->formatResponse($final_response);
                    $response['success'] = true;
                    
                } else {
                    $response['message'] = $this->formatResponse($initial_response);
                    $response['success'] = true;
                }
            } else {
                $response['message'] = "Invalid response from Groq API";
            }


        } catch (\Exception $e) {
            $this->log_debug(['Error in handleQuery' => $e->getMessage()]);
            $response['message'] = "An error occurred: " . $e->getMessage();
        }

        $response['debug'] = $this->debug_log;
        debug_to_console("Final response: " . json_encode($response));

        return json_encode($response);
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