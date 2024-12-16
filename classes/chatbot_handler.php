<?php
namespace mod_moodlechatbot;

defined('MOODLE_INTERNAL') || die();

class chatbot_handler {
    private $api_handler;
    private $memory_manager;
    private $tool_handler;

    public function __construct() {
        $this->api_handler = new ChatbotApiHandler();
        $this->memory_manager = new ChatbotMemoryManager();
        $this->tool_handler = new ChatbotToolHandler();
        
        debugging('Chatbot handler initialized', DEBUG_DEVELOPER);
    }

    public function handleQuery($message) {
        debugging('Debugging: Query received: ' . $message, DEBUG_DEVELOPER);
        
        // Add user message to memory
        $this->memory_manager->addToMemory('user', $message);
        
        // Send message to API with full conversation history
        $initial_response = $this->api_handler->sendToGroq($message, $this->memory_manager->getMemory());
        
        if ($initial_response === false) {
            debugging('Error: Failed to get a response from API', DEBUG_DEVELOPER);
            return "I'm sorry, but I encountered an error while communicating with the AI service.";
        }
        
        debugging('Debugging: Raw response from API: ' . $initial_response, DEBUG_DEVELOPER);

        $decoded_response = json_decode($initial_response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            debugging('Error decoding JSON response: ' . json_last_error_msg(), DEBUG_DEVELOPER);
            return "I'm sorry, but I encountered an error while processing the AI service response.";
        }
        
        $content = $this->api_handler->extractContent($decoded_response);
        if ($content === false) {
            debugging('Unexpected response structure from API', DEBUG_DEVELOPER);
            return "I'm sorry, but I couldn't process your request at this time due to an unexpected response format.";
        }

        // Try to extract and execute tool call from the response
        $tool_call = $this->tool_handler->extractToolCall($content);
        
        if ($tool_call) {
            debugging('Debugging: Tool call detected', DEBUG_DEVELOPER);
            
            try {
                // Execute the tool and get results
                $tool_result = $this->tool_handler->executeTool($tool_call);
                
                // Add tool result to memory as system message
                $this->memory_manager->addToMemory('system', $tool_result);
                
                // Format tool result for API consumption
                $tool_result_message = $this->tool_handler->formatToolResult($tool_call['name'], $tool_result);
                
                // Get final response from API with tool results
                $final_response = $this->api_handler->sendToGroq(
                    $tool_result_message, 
                    $this->memory_manager->getMemory()
                );
                
                if ($final_response === false) {
                    debugging('Error: Failed to get a final response from API', DEBUG_DEVELOPER);
                    return "I'm sorry, but I encountered an error while processing the tool results.";
                }
                
                $formatted_response = $this->api_handler->formatResponse($final_response);
                
                // Add the formatted response to memory
                $this->memory_manager->addToMemory('assistant', $formatted_response);
                
                return $formatted_response;
                
            } catch (\Exception $e) {
                debugging('Error during tool execution: ' . $e->getMessage(), DEBUG_DEVELOPER);
                return "I'm sorry, but I encountered an error while processing your request with the specified tool.";
            }
        } else {
            debugging('Debugging: No tool call detected.', DEBUG_DEVELOPER);
            $formatted_response = $this->api_handler->formatResponse($initial_response);
            
            // Add the response to memory
            $this->memory_manager->addToMemory('assistant', $formatted_response);
            
            return $formatted_response;
        }
    }

    public function clearMemory() {
        $this->memory_manager->clearMemory();
    }
}
