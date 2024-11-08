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

    private function addToMemory($role, $content, $is_tool_result = false) {
        global $SESSION;
        
        // Add new message with metadata
        $SESSION->chatbot_memory[] = [
            'role' => $role,
            'content' => $content,
            'timestamp' => time(),
            'is_tool_result' => $is_tool_result
        ];

        // Keep memory within size limit
        while (count($SESSION->chatbot_memory) > $this->max_memory_size) {
            array_shift($SESSION->chatbot_memory);
        }

        debugging('Memory state after adding message - Memory size: ' . count($SESSION->chatbot_memory), DEBUG_DEVELOPER);
        debugging('Latest memory entry - Role: ' . $role . ', Content: ' . $content, DEBUG_DEVELOPER);
        debugging('Full memory state: ' . print_r($SESSION->chatbot_memory, true), DEBUG_DEVELOPER);
    }

    public function clearMemory() {
        global $SESSION;
        $SESSION->chatbot_memory = [];
        debugging('Conversation memory cleared', DEBUG_DEVELOPER);
    }

    public function handleQuery($message) {
        global $SESSION;
        
        debugging('Debugging: Query received: ' . $message, DEBUG_DEVELOPER);
        debugging('Current memory state before processing: ' . print_r($SESSION->chatbot_memory, true), DEBUG_DEVELOPER);
        
        // Add user message to memory
        $this->addToMemory('user', $message);
        
        // First, try to get a response using existing memory context
        $memory_response = $this->getResponseFromMemory($message);
        if ($memory_response !== false) {
            debugging('Found relevant response in memory', DEBUG_DEVELOPER);
            $this->addToMemory('assistant', $memory_response);
            return $memory_response;
        }
        
        debugging('Debugging: No relevant response found in memory, sending message to Groq', DEBUG_DEVELOPER);
        
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
        
        // Only try to extract tool call from new LLM responses
        $tool_call = $this->extractToolCall($content);
        
        if ($tool_call) {
            debugging('Debugging: Extracted tool call: ' . print_r($tool_call, true), DEBUG_DEVELOPER);
            
            try {
                $tool = $this->tool_manager->get_tool($tool_call['name']);
                debugging('Debugging: Calling tool method: ' . $tool_call['name'] . '->execute(' . json_encode($tool_call['parameters']) . ')', DEBUG_DEVELOPER);
                $tool_result = $tool->execute($tool_call['parameters']);
                debugging('Debugging: Tool Output: ' . print_r($tool_result, true), DEBUG_DEVELOPER);
                
                // Store tool result in memory
                $this->addToMemory('system', $tool_result, true);
                
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

        // Add assistant's response to memory
        $this->addToMemory('assistant', $formatted_response);
        
        // Log final memory state
        debugging('Final memory state after processing: ' . print_r($SESSION->chatbot_memory, true), DEBUG_DEVELOPER);

        debugging('Debugging: Sending response to user: ' . $formatted_response, DEBUG_DEVELOPER);
        return $formatted_response;
    }

    private function getResponseFromMemory($message) {
        global $SESSION;
        
        // Simple similarity check for questions about assignments
        $assignment_keywords = ['assignment', 'homework', 'due', 'deadline'];
        $is_assignment_query = false;
        foreach ($assignment_keywords as $keyword) {
            if (stripos($message, $keyword) !== false) {
                $is_assignment_query = true;
                break;
            }
        }
        
        if ($is_assignment_query) {
            // Look for recent tool results about assignments
            foreach (array_reverse($SESSION->chatbot_memory) as $entry) {
                if (isset($entry['is_tool_result']) && $entry['is_tool_result']) {
                    // Found a relevant tool result, get the next assistant response
                    $found_tool_result = false;
                    foreach ($SESSION->chatbot_memory as $response) {
                        if ($found_tool_result && $response['role'] === 'assistant') {
                            return $response['content'];
                        }
                        if ($response === $entry) {
                            $found_tool_result = true;
                        }
                    }
                }
            }
        }
        
        return false;
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

        // Add conversation history from session
        foreach ($SESSION->chatbot_memory as $exchange) {
            // Skip tool results in the conversation history sent to Groq
            if (isset($exchange['is_tool_result']) && $exchange['is_tool_result']) {
                continue;
            }
            $messages[] = [
                'role' => $exchange['role'],
                'content' => $exchange['content']
            ];
        }

        // Add current message if it's not already part of a conversation
        if (!is_array($message)) {
            $messages[] = ['role' => 'user', 'content' => $message];
        }

        // Log the messages being sent to Groq
        debugging('Messages being sent to Groq: ' . print_r($messages, true), DEBUG_DEVELOPER);
    
        $payload = json_encode([
            'model' => 'llama-3.2-90b-text-preview',
            'messages' => $messages
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
                'parameters' => [],
                'note' => 'format the output according to the query. For example if the query contains the word list the the output should be formatted as a list i.e. each item below the other. '
            ],
            [
                'name' => 'get_upcoming_assignments',
                'description' => 'Retrieves all future assignments with their due dates and days until due',
                'parameters' => [],
                'note' => 'The tool returns all assignments - you should filter and process the results based on the user\'s query, such as specifying timeframes (e.g., next week, next month).'
            ]
        ];
    
        return "You are a helpful assistant for a Moodle learning management system. " .
               "You will answer queries politely, accurately, and concisely even if the query is not Moodle related. " .
               "You have access to the following tools:\n\n" .
               json_encode($tools, JSON_PRETTY_PRINT) . "\n\n" .
               "If a user's query requires using a tool, respond with ONLY a JSON object containing " .
               "a 'tool_call' key with 'name' and 'parameters' subkeys. " .
               "Here is an example of the expected JSON format:\n\n" .
               "{\n" .
               "  \"tool_call\": {\n" .
               "    \"name\": \"tool_name\",\n" .
               "    \"parameters\": {}\n" .
               "  }\n" .
               "}\n\n" .
               "After receiving tool results, provide a natural language response to the user's query, filtering and processing " .
               "the assignments based on the user's requirements (e.g., next month, this week, etc.). " .
               "Use the conversation history to maintain context and provide more relevant responses. " .
               "When a user refers to previous messages (e.g., 'tell me another', 'repeat that'), look at the conversation " .
               "history to understand the context and provide an appropriate response.";
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
