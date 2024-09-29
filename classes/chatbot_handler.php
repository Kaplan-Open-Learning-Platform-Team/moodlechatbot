<?php
// classes/chatbot_handler.php
header('Content-Type: application/json');
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
    }

    private function register_tools() {
        $this->tool_manager->register_tool('get_enrolled_courses', 'mod_moodlechatbot\tools\get_enrolled_courses');
    }

    public function handleQuery($message) {
        try {
            $initial_response = $this->sendToGroq($message);
            $decoded_response = json_decode($initial_response, true);
            
    
            if (isset($decoded_response['choices'][0]['message']['tool_calls'])) {
                // ... (rest of the tool call handling code)
    
                $final_response = $this->sendToGroq($message, $tool_results);
                debugging('Final Groq response: ' . $final_response, DEBUG_DEVELOPER);
                $formatted_response = $this->formatResponse($final_response);
            } else {
                $formatted_response = $this->formatResponse($initial_response);
            }
    
            return json_encode(['response' => $formatted_response]);
        } catch (Exception $e) {
            debugging('Error in handleQuery: ' . $e->getMessage(), DEBUG_DEVELOPER);
            return json_encode(['error' => $e->getMessage()]);
        }
    }

            // Send the tool results back to Groq for final response formatting
            $final_response = $this->sendToGroq($message, $tool_results);
            debugging('Final Groq response: ' . $final_response, DEBUG_DEVELOPER);
            return $this->formatResponse($final_response);
        }
        
        return $this->formatResponse($initial_response);
    }

    private function sendToGroq($message, $tool_results = null) {
        $curl = curl_init();

        $payload = [
            'model' => 'llama3-groq-70b-8192-tool-use-preview',
            'messages' => [
                ['role' => 'system', 'content' => $this->getSystemPrompt()],
                ['role' => 'user', 'content' => $message]
            ],
            'tools' => [
                [
                    'type' => 'function',
                    'function' => [
                        'name' => 'get_enrolled_courses',
                        'description' => 'Get the courses the current user is enrolled in',
                        'parameters' => [
                            'type' => 'object',
                            'properties' => [],
                            'required' => []
                        ]
                    ]
                ]
            ],
            'tool_choice' => 'auto',
            'max_tokens' => 4096
        ];

        if ($tool_results !== null) {
            $payload['messages'][] = [
                'role' => 'assistant',
                'content' => null,
                'tool_calls' => $tool_results
            ];
        }

        curl_setopt_array($curl, [
            CURLOPT_URL => $this->groq_api_url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($payload),
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
               "You have access to the get_enrolled_courses tool to retrieve the courses " .
               "the current user is enrolled in. Use this tool when asked about courses. " .
               "After receiving tool results, provide a natural language response to the user.";
    }

    private function formatResponse($response) {
        $decoded = json_decode($response, true);
        return $decoded['choices'][0]['message']['content'] ?? "No response from Groq API";
    }
}
