<?php

function debug_to_console($data) {
    if (is_array($data) || is_object($data)) {
        $output = json_encode($data);
    } else {
        $output = strval($data);  // Convert to string
    }
    
    // Escape any single quotes and newlines
    $output = str_replace(
        array("\\", "'", "\r", "\n"), 
        array("\\\\", "\\'", "\\r", "\\n"), 
        $output
    );
    
    // Immediately output to console
    echo "<script>console.log('[MOD_MOODLECHATBOT] " . $output . "');</script>";
    
    // Also store in global log for potential later use
    global $debug_log;
    if (!isset($debug_log)) {
        $debug_log = [];
    }
    $debug_log[] = $output;
}

function output_debug_log() {
    global $debug_log;
    if (isset($debug_log) && is_array($debug_log)) {
        foreach ($debug_log as $message) {
            echo "<script>console.log('[MOD_MOODLECHATBOT_SUMMARY] " . $message . "');</script>";
        }
        // Clear the log after outputting
        $debug_log = [];
    }
}