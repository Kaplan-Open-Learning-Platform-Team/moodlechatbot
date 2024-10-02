<?php

function debug_to_console($data) {
    global $debug_log;
    if (!isset($debug_log)) {
        $debug_log = [];
    }
    if (is_array($data) || is_object($data)) {
        $output = json_encode($data);
    } else {
        $output = $data;
    }
    $debug_log[] = $output; // Store as string.
}


function output_debug_log() {  // New function to output the log.
    global $debug_log;
    if (isset($debug_log) && is_array($debug_log)) {
        foreach ($debug_log as $message) {
            echo "<script>console.log('[MOD_MOODLECHATBOT] " . addslashes($message) . "');</script>";
        }

        // prevent logs from accumulating and being printed repeatedly
        unset($GLOBALS['debug_log']);

    }
}
?>