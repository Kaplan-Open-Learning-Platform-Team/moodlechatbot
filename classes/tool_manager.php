<?php

namespace mod_moodlechatbot;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/tool.php');

class tool_manager {
    private $tools = [];

    public function register_tool($name, $class) {
        $this->tools[$name] = '\\' . ltrim($class, '\\');
    }

    public function get_tool($name) {
        if (!isset($this->tools[$name])) {
            throw new \moodle_exception('toolnotfound', 'mod_moodlechatbot', '', $name);
        }

        $class = $this->tools[$name];

        if (!class_exists($class)) {
            throw new \moodle_exception('classnotfound', 'mod_moodlechatbot', '', $class);
        }

        $reflection = new \ReflectionClass($class);
        if (!$reflection->isSubclassOf('mod_moodlechatbot\tool')) {
            throw new \moodle_exception('invalidtoolclass', 'mod_moodlechatbot', '', $class);
        }

        return new $class();
    }

    public function execute_tool($name, $params = []) {
        try {
            $tool = $this->get_tool($name);
            $result = $tool->execute($params);
            return $result;
        } catch (\Exception $e) {
            throw new \moodle_exception('toolexecutionerror', 'mod_moodlechatbot', '', $name . ': ' . $e->getMessage());
        }
    }
}
?>