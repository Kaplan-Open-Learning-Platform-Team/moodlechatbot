<?php
namespace mod_moodlechatbot;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/tool.php');


function debug_to_console($data) {
    $output = $data;
    if (is_array($output) || is_object($output)) {
        $output = json_encode($output);
    }
    echo "<script>console.log('[MOD_MOODLECHATBOT] " . addslashes($output) . "');</script>";
}


class tool_manager {
    private $tools = [];

    public function register_tool($name, $class) {
        $this->tools[$name] = '\\' . ltrim($class, '\\');
        debug_to_console(['Registered tool:' => $name, 'With class:' => $this->tools[$name]]);
    }

    public function get_tool($name) {
        debug_to_console(['Attempting to get tool:' => $name]);

        if (!isset($this->tools[$name])) {
            debug_to_console(['Tool not found:' => $name]);
            throw new \moodle_exception('toolnotfound', 'mod_moodlechatbot', '', $name);
        }

        $class = $this->tools[$name];
        debug_to_console(['Class name:' => $class]);

        if (!class_exists($class)) {
            debug_to_console(['Class not found:' => $class]);
            throw new \moodle_exception('classnotfound', 'mod_moodlechatbot', '', $class);
        }

        $reflection = new \ReflectionClass($class);
        if (!$reflection->isSubclassOf('mod_moodlechatbot\tool')) {
            debug_to_console(['Class does not extend mod_moodlechatbot\\tool:' => $class]);
            throw new \moodle_exception('invalidtoolclass', 'mod_moodlechatbot', '', $class);
        }

        return new $class();
    }

    public function execute_tool($name, $params = []) {
        debug_to_console(['Executing tool:' => $name, 'With parameters:' => $params]);

        try {
            $tool = $this->get_tool($name);
            $result = $tool->execute($params);
            debug_to_console(['Tool execution result:' => $result]);
            return $result;
        } catch (\Exception $e) {
            debug_to_console(['Error executing tool:' => $name, 'Error message:' => $e->getMessage()]);
            throw new \moodle_exception('toolexecutionerror', 'mod_moodlechatbot', '', $name . ': ' . $e->getMessage());
        }
    }
}