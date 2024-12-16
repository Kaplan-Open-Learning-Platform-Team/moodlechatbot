<?php
namespace mod_moodlechatbot;

defined('MOODLE_INTERNAL') || die();

class ChatbotApiHandler {
    private $api_provider;
    private $groq_api_key;
    private $gemini_api_key;
    private $groq_model;
    private $gemini_model;
    private $groq_api_url = 'https://api.groq.com/openai/v1/chat/completions';
    private $gemini_api_base_url = 'https://generativelanguage.googleapis.com/v1beta/models/';

    public function __construct() {
        $this->api_provider = get_config('mod_moodlechatbot', 'api_provider');
        $this->groq_api_key = get_config('mod_moodlechatbot', 'groq_api_key');
        $this->gemini_api_key = get_config('mod_moodlechatbot', 'gemini_api_key');
        
        // Get model names with defaults if not set
        $this->groq_model = get_config('mod_moodlechatbot', 'groq_model');
        if (empty($this->groq_model)) {
            $this->groq_model = 'llama-3.2-90b-text-preview';
            debugging('No Groq model specified, using default: ' . $this->groq_model, DEBUG_DEVELOPER);
        }
        
        $this->gemini_model = get_config('mod_moodlechatbot', 'gemini_model');
        if (empty($this->gemini_model)) {
            $this->gemini_model = 'gemini-pro';
            debugging('No Gemini model specified, using default: ' . $this->gemini_model, DEBUG_DEVELOPER);
        }
        
        debugging('API handler initialized with ' . $this->api_provider . ' using model: ' . 
            ($this->api_provider === 'gemini' ? $this->gemini_model : $this->groq_model), DEBUG_DEVELOPER);
    }

    public function sendToGroq($message, $memory) {
        if (empty($this->groq_api_key)) {
            debugging('Error: No Groq API key configured', DEBUG_DEVELOPER);
            return false;
        }

        $curl = curl_init();
    
        // Prepare messages array with conversation history
        $messages = [
            ['role' => 'system', 'content' => $this->getSystemPrompt()]
        ];

        // Clean and add conversation history
        $cleaned_memory = $this->cleanMemoryForLLM($memory);
        foreach ($cleaned_memory as $exchange) {
            $messages[] = $exchange;
        }

        // Add current message if it's not already part of a conversation
        if (!is_array($message)) {
            $messages[] = ['role' => 'user', 'content' => $message];
        }

        debugging('Using Groq model: ' . $this->groq_model, DEBUG_DEVELOPER);
    
        $payload = json_encode([
            'model' => $this->groq_model,
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

    public function sendToGemini($message, $memory) {
        if (empty($this->gemini_api_key)) {
            debugging('Error: No Gemini API key configured', DEBUG_DEVELOPER);
            return false;
        }

        $curl = curl_init();

        // Prepare conversation history
        $cleaned_memory = $this->cleanMemoryForLLM($memory);
        $conversation = "";
        foreach ($cleaned_memory as $exchange) {
            $conversation .= ($exchange['role'] === 'user' ? 'User: ' : 'Assistant: ') . $exchange['content'] . "\n";
        }

        // Add system prompt and current message
        $full_prompt = $this->getSystemPrompt() . "\n\nConversation History:\n" . $conversation . 
            "User: " . $message . "\nAssistant:";

        debugging('Using Gemini model: ' . $this->gemini_model, DEBUG_DEVELOPER);

        $payload = json_encode([
            'contents' => [
                [
                    'parts' => [
                        ['text' => $full_prompt]
                    ]
                ]
            ],
            'generationConfig' => [
                'temperature' => 0.7,
                'maxOutputTokens' => 1000,
            ]
        ]);

        $url = $this->gemini_api_base_url . $this->gemini_model . ':generateContent?key=' . $this->gemini_api_key;

        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_HTTPHEADER => [
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
            debugging('Error: Empty response from Gemini API', DEBUG_DEVELOPER);
            return false;
        }

        return $response;
    }

    public function extractContent($decoded_response) {
        if ($this->api_provider === 'gemini') {
            if (isset($decoded_response['candidates'][0]['content']['parts'][0]['text'])) {
                return $decoded_response['candidates'][0]['content']['parts'][0]['text'];
            }
        } else { // Groq
            if (isset($decoded_response['choices'][0]['message']['content'])) {
                return $decoded_response['choices'][0]['message']['content'];
            }
        }
        return false;
    }

    public function formatResponse($response) {
        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE) {
            if ($this->api_provider === 'gemini') {
                if (isset($decoded['candidates'][0]['content']['parts'][0]['text'])) {
                    $formatted = $decoded['candidates'][0]['content']['parts'][0]['text'];
                } else {
                    debugging('Error accessing Gemini response content', DEBUG_DEVELOPER);
                    $formatted = "I'm sorry, but I couldn't generate a response at this time.";
                }
            } else { // Groq
                if (isset($decoded['choices'][0]['message']['content'])) {
                    $formatted = $decoded['choices'][0]['message']['content'];
                } else {
                    debugging('Error accessing Groq response content', DEBUG_DEVELOPER);
                    $formatted = "I'm sorry, but I couldn't generate a response at this time.";
                }
            }
        } else {
            debugging('Error decoding response: ' . json_last_error_msg(), DEBUG_DEVELOPER);
            $formatted = "I'm sorry, but I couldn't generate a response at this time.";
        }
        return $formatted;
    }

    private function cleanMemoryForLLM($memory) {
        $cleaned = [];
        foreach ($memory as $entry) {
            // Convert content to string if it's an array/object
            $content = $entry['content'];
            if (is_array($content) || is_object($content)) {
                $content = json_encode($content, JSON_PRETTY_PRINT);
            }
            
            // Skip empty or purely technical messages
            if (empty(trim($content)) || $content === '{}' || $content === '[]') {
                continue;
            }
            
            // Convert system messages with tool results to assistant messages for better context
            if ($entry['role'] === 'system' && strpos($content, '{') === 0) {
                $entry['role'] = 'assistant';
                $content = "Here are the results I found: " . $content;
            }
            
            // Keep all meaningful messages, including tool responses
            $cleaned[] = [
                'role' => $entry['role'],
                'content' => $content
            ];
        }
        
        debugging('Cleaned memory size: ' . count($cleaned), DEBUG_DEVELOPER);
        return $cleaned;
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
    
        return "
        <general>
        You are a friendly and helpful teaching assistant for Moodle. Your role is to provide clear, accurate, and concise answers to a wide range of questions from users.  These questions can be about Moodle, university life, academic topics or anything else relevant to a student's experience (within reason). Always try to provide a helpful answer; if you don't know, say so politely.
        Aim for a natural, informative conversation that assists the user.  Do not include your reasoning process in your answers.  
        **Important:**  Do not respond to offensive, inappropriate, or harmful requests. If a question requires access to private or sensitive information, politely inform the user that you cannot answer and explain why (e.g., data privacy concerns).
        </general>
        <flow>
        <flow_step_1>
        1. First, check the conversation memory to see if you can provide a response using the information already available. Be mindful that some data in the memory, such as time-sensitive information or rapidly changing course details, may become outdated or unreliable over time.
        Examples of unreliable memory data:

            1. Assignment due dates that have passed
            2. Course enrollments or schedules that have changed
            3. Rapidly evolving current events or news

        </flow_step_1>
        <flow_step_2>
        2. If the memory data is missing, unreliable, or insufficient to fully answer the user's query, you have two options:
            a. Respond in natural language using your own knowledge and reasoning, providing a clear and concise answer.
            b. Make a tool call to retrieve additional information, then incorporate the results into a direct, helpful response.
        </flow_step_2>
        <flow_step_3>
        3. Your final response should directly address the user's original query in a clear, concise manner. Avoid unnecessary details or verbosity. The user's needs should be the primary focus of your response.
        </flow_step_3>
        At no point should you combine a natural language response with a tool call in the same reply. The flow should be either memory-based response, tool call response, or natural language response - never a mixture.
        </flow>
    
        <tools>
        Available tools:
        " . json_encode($tools, JSON_PRETTY_PRINT) . "
        </tools>

        <tool_calling>
        To make a tool call, respond with ONLY this JSON structure:
        {
        \"tool_call\": {
            \"name\": \"tool_name\",
            \"parameters\": {}
        }
        }
        
        After receiving the tool results, incorporate them into a natural language response that directly answers the user's original query
        </tool_calling>";
    }
}
