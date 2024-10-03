<?php
namespace mod_moodlechatbot;

defined('MOODLE_INTERNAL') || die();

class helper_functions {
    // Logs data to Moodle's PHP error log for server-side debugging
    public static function debug_to_console($data) {
        error_log(print_r($data, true));  // This logs to the PHP error log
    }

    // Check if Moodle's debugging mode is enabled
    public static function is_debugging_enabled() {
        return debugging();  // Uses Moodle's built-in debugging function
    }
}
