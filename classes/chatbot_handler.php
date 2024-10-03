<?php
namespace mod_moodlechatbot;

use mod_moodlechatbot\helper_functions;

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
        $this->tool_manager->register_tool('get_enrolled_courses', '\mod_moodlechatbot\tools\get_enrolled_courses');
    }

    public function handleQuery($message) {
        // Send user query to OpenAI (Groq)
        $initial_response = $this->sendToGroq($message);

        // Decode OpenAI response
        $decoded_response = json_decode($initial_response, true);

        // Check if the OpenAI response includes a tool call
        if (isset($decoded_response['choices'][0]['message']['content'])) {
            $content = $decoded_response['choices'][0]['message']['content'];
            $tool_call = json_decode($content, true);

            if (isset($tool_call['tool_call'])) {
                $tool_name = $tool_call['tool_call']['name'];
                $tool_params = $tool_call['tool_call']['parameters'];

                // Execute the tool
                $tool_result = $this->tool_manager->execute_tool($tool_name, $tool_params);

                // Send tool result back to OpenAI for final response formatting
                $final_response = $this->sendToGroq(json_encode([
                    'user_message' => $message,
                    'tool_result' => $tool_result
                ]));

                return $this->formatResponse($final_response, $message);
            }
        }

        // Return the formatted response with possible debug information
        return $this->formatResponse($initial_response, $message);
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
               "You have access to the following tools: get_enrolled_courses. " .
               "If a query requires using a tool, respond with a JSON object. " .
               "After receiving tool results, provide a natural language response.";
    }

    // Format the response and include debugging information if needed
    private function formatResponse($response, $query = null) {
        $decoded = json_decode($response, true);
        $formatted_response = $decoded['choices'][0]['message']['content'] ?? "No response from Groq API";

        // Prepare the response data
        $data = [
            'response' => $formatted_response,
        ];

        // Include debug information if debugging is enabled
        if (helper_functions::is_debugging_enabled()) {
            $data['debug'] = [
                'query' => $query,
                'response' => $response,
            ];
        }

        // Set the content type to JSON and return the data
        header('Content-Type: application/json');
        echo json_encode($data);
    }
}
