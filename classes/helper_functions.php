<?php
namespace mod_moodlechatbot;

// Initialize debug log as a global array
global $DEBUG_LOG;
$DEBUG_LOG = [];

function debug_to_console($data) {
    global $DEBUG_LOG;
    
    if (is_array($data) || is_object($data)) {
        $DEBUG_LOG[] = json_encode($data);
    } else {
        $DEBUG_LOG[] = (string)$data;
    }
}

function get_debug_log() {
    global $DEBUG_LOG;
    return $DEBUG_LOG;
}

function output_debug_log() {
    global $DEBUG_LOG;
    return $DEBUG_LOG ?? [];
}