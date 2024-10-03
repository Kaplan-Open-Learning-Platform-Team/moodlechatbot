<?php
namespace mod_moodlechatbot;

defined('MOODLE_INTERNAL') || die();

class helper_functions {
    // Log data to the browser console
    public static function debug_to_console($data) {
        $output = json_encode($data);  // Convert the data to JSON format
        echo "<script>console.log('Debug Objects: " . addslashes($output) . "');</script>";
    }

    // Optionally check if debugging is enabled (optional, but recommended for security)
    public static function is_debugging_enabled() {
        return debugging();  // Uses Moodle's built-in debugging check
    }
}