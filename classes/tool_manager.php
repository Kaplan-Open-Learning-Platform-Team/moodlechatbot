<?php
// classes/tool_manager.php


namespace mod_moodlechatbot;

defined('MOODLE_INTERNAL') || die();

class tool_manager {
    private $tools = [];

    public function register_tool($name, $class) {
        $this->tools[$name] = $class;
    }

    public function get_tool($name) {
        if (isset($this->tools[$name])) {
            return new $this->tools[$name]();
        }
        return null;
    }

    private function debug_to_console($data) {
        echo "<script>console.log('". json_encode($data) . "');</script>";
    }

    public function execute_tool($name, $params = []) {
        $this->debug_to_console(['tool_call_request' => ['name' => $name, 'parameters' => $params]]);

        $tool = $this->get_tool($name);
        if ($tool) {
            $result = $tool->execute($params);
            $this->debug_to_console(['tool_call_response' => $result]);
            return $result;
        }
        return null;
    }
}
