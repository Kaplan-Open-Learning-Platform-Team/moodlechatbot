<?php
// classes/chatbot_handler.php

namespace mod_moodlechatbot;

defined('MOODLE_INTERNAL') || die();

class chatbot_handler {
    private $groq_api_key;
    private $groq_api_url = 'https://api.groq.com/openai/v1/chat/completions';
    private $tool_manager;
    private $max_memory_size = 10;

    public function __construct() {
        global $SESSION;
        $this->groq_api_key = get_config('mod_moodlechatbot', 'groq_api_key');
        $this->tool_manager = new tool_manager();
        $this->register_tools();
        
        // Initialize session memory if it doesn't exist
        if (!isset($SESSION->chatbot_memory)) {
            $SESSION->chatbot_memory = [];
        }
        
        debugging('Chatbot handler initialized', DEBUG_DEVELOPER);
    }

    private function register_tools() {
        $this->tool_manager->register_tool('get_enrolled_courses', '\mod_moodlechatbot\tools\get_enrolled_courses');
        $this->tool_manager->register_tool('get_upcoming_assignments', '\mod_moodlechatbot\tools\get_upcoming_assignments');
        debugging('Tools registered', DEBUG_DEVELOPER);
    }

    private function addToMemory($role, $content) {
        global $SESSION;
        
        // Convert array/object content to JSON string for system messages (tool results)
        if ($role === 'system' && (is_array($content) || is_object($content))) {
            $content = json_encode($content, JSON_PRETTY_PRINT);
        }
        
        // Add new message
        $SESSION->chatbot_memory[] = [
            'role' => $role,
            'content' => $content,
            'timestamp' => time()
        ];

        // Keep memory within size limit
        while (count($SESSION->chatbot_memory) > $this->max_memory_size) {
            array_shift($SESSION->chatbot_memory);
        }

        debugging('Memory state after adding message - Memory size: ' . count($SESSION->chatbot_memory), DEBUG_DEVELOPER);
        debugging('Latest memory entry - Role: ' . $role . ', Content: ' . print_r($content, true), DEBUG_DEVELOPER);
        debugging('Full memory state: ' . print_r($SESSION->chatbot_memory, true), DEBUG_DEVELOPER);
    }

    public function clearMemory() {
        global $SESSION;
        $SESSION->chatbot_memory = [];
        debugging('Conversation memory cleared', DEBUG_DEVELOPER);
    }

    private function cleanMemoryForLLM($memory) {
        $cleaned = [];
        foreach ($memory as $entry) {
            // Convert content to string if it's an array/object
            $content = $entry['content'];
            if (is_array($content) || is_object($content)) {
                $content = json_encode($content);
            }
            
            // Skip entries that are tool calls
            if (strpos($content, '"tool_call"') !== false) {
                continue;
            }
            
            // Clean up any waiting messages
            if (strpos($content, 'please wait') !== false ||
                strpos($content, 'waiting for') !== false ||
                strpos($content, 'made a request') !== false) {
                continue;
            }
            
            $cleaned[] = [
                'role' => $entry['role'],
                'content' => $content
            ];
        }
        return $cleaned;
    }

    public function handleQuery($message) {
        global $SESSION;
        
        debugging('Debugging: Query received: ' . $message, DEBUG_DEVELOPER);
        debugging('Current memory state before processing: ' . print_r($SESSION->chatbot_memory, true), DEBUG_DEVELOPER);
        
        // Add user message to memory
        $this->addToMemory('user', $message);
        
        // Send message to Groq with full conversation history
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
        
        // Try to extract tool call from the response
        $tool_call = $this->extractToolCall($content);
        
        if ($tool_call) {
            debugging('Debugging: Extracted tool call: ' . print_r($tool_call, true), DEBUG_DEVELOPER);
            
            try {
                $tool = $this->tool_manager->get_tool($tool_call['name']);
                debugging('Debugging: Calling tool method: ' . $tool_call['name'] . '->execute(' . json_encode($tool_call['parameters']) . ')', DEBUG_DEVELOPER);
                $tool_result = $tool->execute($tool_call['parameters']);
                debugging('Debugging: Tool Output: ' . print_r($tool_result, true), DEBUG_DEVELOPER);
                
                // Add tool result to memory as JSON string
                $this->addToMemory('system', $tool_result);
                
                // Send the tool result back to Groq for final response formatting
                $final_response = $this->sendToGroq([
                    'user_message' => $message,
                    'tool_result' => $tool_result
                ]);
                
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

        // Add assistant's response to memory
        $this->addToMemory('assistant', $formatted_response);
        
        // Log final memory state
        debugging('Final memory state after processing: ' . print_r($SESSION->chatbot_memory, true), DEBUG_DEVELOPER);

        debugging('Debugging: Sending response to user: ' . $formatted_response, DEBUG_DEVELOPER);
        return $formatted_response;
    }

    private function extractToolCall($content) {
        debugging('Debugging: Attempting to extract tool call from: ' . $content, DEBUG_DEVELOPER);
        
        // Use regex to find JSON object within the content
        if (preg_match('/```json\s*(.*?)\s*```/s', $content, $matches)) {
            $json_string = $matches[1];
        } else {
            $json_string = $content;
        }
        
        // Parse the JSON string
        $parsed = json_decode($json_string, true);
        
        if (json_last_error() === JSON_ERROR_NONE && isset($parsed['tool_call'])) {
            debugging('Debugging: Successfully extracted tool call', DEBUG_DEVELOPER);
            return $parsed['tool_call'];
        }
        
        debugging('Debugging: Failed to extract tool call', DEBUG_DEVELOPER);
        return null;
    }

    private function sendToGroq($message) {
        global $SESSION;
        $curl = curl_init();
    
        // Prepare messages array with conversation history
        $messages = [
            ['role' => 'system', 'content' => $this->getSystemPrompt()]
        ];

        // Clean and add conversation history
        $cleaned_memory = $this->cleanMemoryForLLM($SESSION->chatbot_memory);
        foreach ($cleaned_memory as $exchange) {
            $messages[] = $exchange;
        }

        // Add current message if it's not already part of a conversation
        if (!is_array($message)) {
            $messages[] = ['role' => 'user', 'content' => $message];
        }

        // Log the messages being sent to Groq
        debugging('Messages being sent to Groq: ' . print_r($messages, true), DEBUG_DEVELOPER);
    
        $payload = json_encode([
            'model' => 'llama-3.2-90b-text-preview',
            'messages' => $messages,
            'temperature' => 0.7,
            'max_tokens' => 1000
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
        $tools = [
            [
                'name' => 'get_enrolled_courses',
                'description' => 'Retrieves the courses the current user is enrolled in',
                'parameters' => []
            ],
            [
                'name' => 'get_upcoming_assignments',
                'description' => 'Retrieves all future assignments with their due dates and days until due',
                'parameters' => []
            ]
        ];
    
        return "<general>You are a helpful assistant for a Moodle learning management system. You provide clear, accurate, and concise responses to all queries, whether Moodle-related or not.</general>

        <flow>Prioritize using information from chatbot_memory. System messages contain tool results. Be aware that some information (e.g., time-sensitive data) may be outdated. If memory data isn't sufficient or reliable, respond in natural language *or* make a tool call, but never both. After receiving tool results, use them to respond in natural language. Only include code if making a tool call.</flow>
        
        <tools>Available tools:
        ```json
        [
          {
            \"name\": \"get_enrolled_courses\",
            \"description\": \"Retrieves the courses the current user is enrolled in\",
            \"parameters\": []
          },
          {
            \"name\": \"get_upcoming_assignments\",
            \"description\": \"Retrieves all future assignments with their due dates and days until due\",
            \"parameters\": []
          }
          // ... other tools
        ]
        ```</tools>
        
        <tool_calls>To make a tool call, respond with **only** this JSON:
        ```json
        {
          \"tool_call\": {
            \"name\": \"tool_name\",
            \"parameters\": {} 
          }
        }
        ```</tool_calls>";

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
