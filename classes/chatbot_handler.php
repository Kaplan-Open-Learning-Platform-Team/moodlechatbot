<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Chatbot handler for the Moodle Chatbot plugin.
 *
 * @package    mod_moodlechatbot
 * @copyright  2024 Your Name &lt;your@email.com&gt;
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_moodlechatbot;

defined('MOODLE_INTERNAL') || die();

class chatbot_handler {
    private $groq_api_key;
    private $groq_api_url = 'https://api.groq.com/openai/v1/chat/completions';
    private $tool_manager;
    private $debug_log = [];

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
        $response = ['success' => false, 'message' => '', 'debug' => []];

        try {
            $this->log_debug("Handling query: " . $message);
            
            $initial_response = $this->sendToGroq($message);
            $this->log_debug("Initial Groq response: " . $initial_response);
            
            $decoded_response = json_decode($initial_response, true);
            
            if (!isset($decoded_response['choices'][0]['message']['content'])) {
                throw new \Exception("Unexpected response format from Groq API");
            }

            $content = $decoded_response['choices'][0]['message']['content'];
            $this->log_debug("Groq content: " . $content);
            
            // Check if the content is a valid JSON
            $tool_call = json_decode($content, true);
            
            if (json_last_error() === JSON_ERROR_NONE && isset($tool_call['tool_call'])) {
                $tool_name = $tool_call['tool_call']['name'];
                $tool_params = $tool_call['tool_call']['parameters'];
                
                $this->log_debug("Executing tool: " . $tool_name . " with params: " . json_encode($tool_params));
                
                $tool_result = $this->tool_manager->execute_tool($tool_name, $tool_params);
                $this->log_debug("Tool result: " . json_encode($tool_result));
                
                // Send the tool result back to Groq for final response formatting
                $final_response = $this->sendToGroq(json_encode([
                    'user_message' => $message,
                    'tool_result' => $tool_result
                ]));
                
                $this->log_debug("Final Groq response: " . $final_response);
                
                $response['message'] = $this->formatResponse($final_response);
            } else {
                // If no valid tool call is found, return the initial response
                $this->log_debug("No valid tool call found, returning initial response");
                $response['message'] = $this->formatResponse($initial_response);
            }

            $response['success'] = true;
        } catch (\Exception $e) {
            $this->log_debug("Error in handleQuery: " . $e->getMessage());
            $response['message'] = "An error occurred: " . $e->getMessage();
        }

        $response['debug'] = $this->debug_log;
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
        if ($response === false) {
            throw new \Exception(curl_error($curl));
        }
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
        if (!isset($decoded['choices'][0]['message']['content'])) {
            throw new \Exception("Unexpected response format from Groq API");
        }
        return $decoded['choices'][0]['message']['content'];
    }

    private function log_debug($message) {
        $this->debug_log[] = $message;
    }
}
