<?php
namespace mod_moodlechatbot;

defined('MOODLE_INTERNAL') || die();

class ChatbotToolHandler {
    private $tool_manager;

    public function __construct() {
        $this->tool_manager = new tool_manager();
        $this->register_tools();
    }

    private function register_tools() {
        $this->tool_manager->register_tool('get_enrolled_courses', '\mod_moodlechatbot\tools\get_enrolled_courses');
        $this->tool_manager->register_tool('get_upcoming_assignments', '\mod_moodlechatbot\tools\get_upcoming_assignments');
        debugging('Tools registered', DEBUG_DEVELOPER);
    }

    public function extractToolCall($content) {
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

    public function executeTool($tool_call) {
        if (!$tool_call || !isset($tool_call['name'])) {
            throw new \Exception('Invalid tool call format');
        }

        debugging('Debugging: Executing tool: ' . $tool_call['name'], DEBUG_DEVELOPER);
        
        try {
            $tool = $this->tool_manager->get_tool($tool_call['name']);
            debugging('Debugging: Calling tool method: ' . $tool_call['name'] . '->execute(' . 
                json_encode($tool_call['parameters']) . ')', DEBUG_DEVELOPER);
            
            $result = $tool->execute($tool_call['parameters']);
            debugging('Debugging: Tool Output: ' . print_r($result, true), DEBUG_DEVELOPER);
            
            return $result;
        } catch (\Exception $e) {
            debugging('Error during tool execution: ' . $e->getMessage(), DEBUG_DEVELOPER);
            throw $e;
        }
    }

    public function formatToolResult($tool_name, $result) {
        return "Here is the result of the " . $tool_name . " tool:\n" . 
            json_encode($result, JSON_PRETTY_PRINT) . 
            "\n\nPlease provide a natural language response based on this data.";
    }
}
