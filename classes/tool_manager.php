<?php

namespace mod_moodlechatbot;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/tool.php');

class tool_manager {
    private $tools = [];

    public function register_tool($name, $class) {
        $this->tools[$name] = '\\' . ltrim($class, '\\');
        debugging("Tool registered: $name => $class", DEBUG_DEVELOPER);
    }

    public function get_tool($name) {
        debugging("Attempting to get tool: $name", DEBUG_DEVELOPER);
        if (!isset($this->tools[$name])) {
            debugging("Tool not found: $name", DEBUG_DEVELOPER);
            throw new \moodle_exception('toolnotfound', 'mod_moodlechatbot', '', $name);
        }

        $class = $this->tools[$name];
        debugging("Tool class found: $class", DEBUG_DEVELOPER);

        if (!class_exists($class)) {
            debugging("Class not found: $class", DEBUG_DEVELOPER);
            throw new \moodle_exception('classnotfound', 'mod_moodlechatbot', '', $class);
        }

        $reflection = new \ReflectionClass($class);
        if (!$reflection->isSubclassOf('mod_moodlechatbot\tool')) {
            debugging("Invalid tool class (does not extend mod_moodlechatbot\tool): $class", DEBUG_DEVELOPER);
            throw new \moodle_exception('invalidtoolclass', 'mod_moodlechatbot', '', $class);
        }

        debugging("Creating instance of tool: $class", DEBUG_DEVELOPER);
        return new $class();
    }

    public function execute_tool($name, $params = []) {
        debugging("Starting execution of tool: $name with params: " . print_r($params, true), DEBUG_DEVELOPER);
        try {
            $tool = $this->get_tool($name);
            debugging("Tool instance created, calling execute method", DEBUG_DEVELOPER);
            $result = $tool->execute($params);
            debugging("Tool execution completed. Result: " . print_r($result, true), DEBUG_DEVELOPER);
            return $result;
        } catch (\Exception $e) {
            debugging("Error executing tool $name: " . $e->getMessage() . "\n" . $e->getTraceAsString(), DEBUG_DEVELOPER);
            throw new \moodle_exception('toolexecutionerror', 'mod_moodlechatbot', '', $name . ': ' . $e->getMessage());
        }
    }
}
?>
