<?php
// classes/tool_manager.php

namespace mod_moodlechatbot;

defined('MOODLE_INTERNAL') || die();

class tool_manager {
    private $tools = [];

    public function register_tool($name, $class) {
        $this->tools[$name] = '\\' . ltrim($class, '\\');
    }

    public function get_tool($name) {
        debugging('Attempting to get tool: ' . $name, DEBUG_DEVELOPER);
        if (!isset($this->tools[$name])) {
            debugging('Tool not found: ' . $name, DEBUG_DEVELOPER);
            throw new \moodle_exception('tooltnotfound', 'mod_moodlechatbot', '', $name);
        }
        $class = $this->tools[$name];
        debugging('Class name: ' . $class, DEBUG_DEVELOPER);
        if (!class_exists($class)) {
            debugging('Class not found: ' . $class, DEBUG_DEVELOPER);
            throw new \moodle_exception('classnotfound', 'mod_moodlechatbot', '', $class);
        }
        return new $class();
    }

    public function execute_tool($name, $params = []) {
        $tool = $this->get_tool($name);
        if ($tool) {
            return $tool->execute($params);
        }
        return null;
    }
}
