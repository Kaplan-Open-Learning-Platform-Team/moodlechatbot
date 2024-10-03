<?php

function debug_to_console($data) {
    try {
        // Convert data to string safely
        if (is_array($data) || is_object($data)) {
            $output = print_r($data, true);
        } else {
            $output = strval($data);
        }
        
        // Escape special characters
        $output = str_replace(
            array("\\", "'", "\r", "\n"), 
            array("\\\\", "\\'", "\\r", "\\n"), 
            $output
        );
        
        // Log to PHP error log as backup
        error_log("[MOD_MOODLECHATBOT] " . $output);
        
        // Output to browser console
        echo "<script>console.log('[MOD_MOODLECHATBOT] " . $output . "');</script>";
        
    } catch (\Exception $e) {
        error_log("Error in debug_to_console: " . $e->getMessage());
    }
}

function output_debug_log() {
    global $debug_log;
    if (isset($debug_log) && is_array($debug_log)) {
        echo "<script>console.log('=== MOD_MOODLECHATBOT DEBUG SUMMARY ===');</script>";
        foreach ($debug_log as $message) {
            echo "<script>console.log(" . json_encode($message) . ");</script>";
        }
        echo "<script>console.log('=== END DEBUG SUMMARY ===');</script>";
    }
}