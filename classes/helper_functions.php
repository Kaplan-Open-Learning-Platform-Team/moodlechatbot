<?php

function debug_to_console($data) {
    if (is_array($data) || is_object($data)) {
        $output = json_encode($data);
    } else {
        $output = $data;
    }
    
    // Escape any quotes that might break the JavaScript
    $output = str_replace('"', '\"', $output);
    
    echo "<script>console.log(\"[MOD_MOODLECHATBOT] " . $output . "\");</script>";
}

function output_debug_log() {
    global $debug_log;
    if (isset($debug_log) && is_array($debug_log)) {
        foreach ($debug_log as $message) {
            echo "<script>console.log(\"[MOD_MOODLECHATBOT_SUMMARY] " . str_replace('"', '\"', $message) . "\");</script>";
        }
        // Clear the log after outputting
        unset($GLOBALS['debug_log']);
    }
}