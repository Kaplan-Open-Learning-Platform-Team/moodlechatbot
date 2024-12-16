<?php
namespace mod_moodlechatbot;

defined('MOODLE_INTERNAL') || die();

class ChatbotMemoryManager {
    private $max_memory_size = 10;

    public function __construct() {
        global $SESSION;
        // Initialize session memory if it doesn't exist
        if (!isset($SESSION->chatbot_memory)) {
            $SESSION->chatbot_memory = [];
        }
    }

    public function addToMemory($role, $content) {
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
    }

    public function clearMemory() {
        global $SESSION;
        $SESSION->chatbot_memory = [];
        debugging('Conversation memory cleared', DEBUG_DEVELOPER);
    }

    public function getMemory() {
        global $SESSION;
        return $SESSION->chatbot_memory;
    }

    public function setMaxMemorySize($size) {
        $this->max_memory_size = $size;
    }
}
