<?php
namespace mod_moodlechatbot;


defined('MOODLE_INTERNAL') || die();

class debug_helper {
    private static $logs = [];

    public static function log($message, $data = null) {
        if (debugging()) {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            $caller = isset($backtrace[1]['function']) ? $backtrace[1]['function'] : '';
            $log = [
                'time' => microtime(true),
                'caller' => $caller,
                'message' => $message,
                'data' => $data
            ];
            self::$logs[] = $log;
        }
    }

    public static function get_logs() {
        return self::$logs;
    }

    public static function clear_logs() {
        self::$logs = [];
    }
}
