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
    $debug_log[] = $output;
}

?>