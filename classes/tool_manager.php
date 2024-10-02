<?php

namespace mod_moodlechatbot;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/tool.php');


class tool_manager {
    private $tools = [];

    public function register_tool($name, $class) {
        $this->tools[$name] = '\\' . ltrim($class, '\\');
        global $debug_log;
        if (!isset($debug_log)) { $debug_log = []; }
        $debug_log[] = json_encode(['Registered tool:' => $name, 'With class:' => $this->tools[$name]]);

    }

    public function get_tool($name) {
        global $debug_log;
        if (!isset($debug_log)) { $debug_log = []; }
        $debug_log[] = json_encode(['Attempting to get tool:' => $name]);

        if (!isset($this->tools[$name])) {
            $debug_log[] = json_encode(['Tool not found:' => $name]);
            throw new \moodle_exception('toolnotfound', 'mod_moodlechatbot', '', $name);
        }

        $class = $this->tools[$name];
        $debug_log[] = json_encode(['Class name:' => $class]);

        if (!class_exists($class)) {
            $debug_log[] = json_encode(['Class not found:' => $class]);
            throw new \moodle_exception('classnotfound', 'mod_moodlechatbot', '', $class);
        }

        $reflection = new \ReflectionClass($class);
        if (!$reflection->isSubclassOf('mod_moodlechatbot\tool')) {
            $debug_log[] = json_encode(['Class does not extend mod_moodlechatbot\\tool:' => $class]);
            throw new \moodle_exception('invalidtoolclass', 'mod_moodlechatbot', '', $class);
        }

        return new $class();
    }

    public function execute_tool($name, $params = []) {
        global $debug_log;
        if (!isset($debug_log)) { $debug_log = []; }
        $debug_log[] = json_encode(['Executing tool:' => $name, 'With parameters:' => $params]);
        try {
            $tool = $this->get_tool($name);
            $result = $tool->execute($params);
            $debug_log[] = json_encode(['Tool execution result:' => $result]);

            return $result;
        } catch (\Exception $e) {
            $debug_log[] = json_encode(['Error executing tool:' => $name, 'Error message:' => $e->getMessage()]);
            throw new \moodle_exception('toolexecutionerror', 'mod_moodlechatbot', '', $name . ': ' . $e->getMessage());
        }
    }
}
?>