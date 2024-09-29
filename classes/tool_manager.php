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
        if (isset($this->tools[$name])) {
            return new $this->tools[$name]();
        }
        return null;
    }

    public function execute_tool($name, $params = []) {
        $tool = $this->get_tool($name);
        if ($tool) {
            return $tool->execute($params);
        }
        return null;
    }
}
