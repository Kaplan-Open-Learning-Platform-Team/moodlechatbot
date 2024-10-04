<?php
namespace mod_moodlechatbot;

defined('MOODLE_INTERNAL') || die();

class debug_helper {
    public static function log($message, $data = null) {
        global $CFG, $SESSION;
        
        if (!isset($SESSION->moodlechatbot_logs)) {
            $SESSION->moodlechatbot_logs = [];
        }

        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
        $caller = isset($backtrace[1]['function']) ? $backtrace[1]['function'] : '';
        $log = [
            'time' => microtime(true),
            'caller' => $caller,
            'message' => $message,
            'data' => $data
        ];
        
        $SESSION->moodlechatbot_logs[] = $log;

        // Always log to PHP error log
        error_log("MoodleChatbot Debug: " . json_encode($log));

        // If debugging is enabled, also log to Moodle debug output
        if (debugging()) {
            debugging("MoodleChatbot Debug: " . json_encode($log), DEBUG_DEVELOPER);
        }

        // If running from CLI, output to console
        if (PHP_SAPI === 'cli') {
            fwrite(STDERR, "MoodleChatbot Debug: " . json_encode($log) . PHP_EOL);
        }
    }

    public static function get_logs() {
        global $SESSION;
        return isset($SESSION->moodlechatbot_logs) ? $SESSION->moodlechatbot_logs : [];
    }

    public static function clear_logs() {
        global $SESSION;
        $SESSION->moodlechatbot_logs = [];
    }

    public static function display_logs() {
        global $OUTPUT;
        
        $logs = self::get_logs();
        if (!empty($logs)) {
            echo $OUTPUT->box_start('generalbox', 'moodlechatbot-debug-log');
            echo "<h3>MoodleChatbot Debug Log</h3>";
            echo "<pre>" . htmlspecialchars(json_encode($logs, JSON_PRETTY_PRINT)) . "</pre>";
            echo $OUTPUT->box_end();
        }
    }
}

// Function to be called from JavaScript
function output_debug_log() {
    return debug_helper::get_logs();
}
