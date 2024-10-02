<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Tool manager for the Moodle Chatbot plugin.
 *
 * @package    mod_moodlechatbot
 * @copyright  2024 Your Name &lt;your@email.com&gt;
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_moodlechatbot;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/tool.php');

class tool_manager {
    private $tools = [];

    public function register_tool($name, $class) {
        $this->tools[$name] = '\\' . ltrim($class, '\\');
        debugging("Registered tool: $name with class: {$this->tools[$name]}", DEBUG_DEVELOPER);
    }

    public function get_tool($name) {
        debugging("Attempting to get tool: $name", DEBUG_DEVELOPER);
        if (!isset($this->tools[$name])) {
            debugging("Tool not found: $name", DEBUG_DEVELOPER);
            throw new \moodle_exception('toolnotfound', 'mod_moodlechatbot', '', $name);
        }
        $class = $this->tools[$name];
        debugging("Class name: $class", DEBUG_DEVELOPER);
        if (!class_exists($class)) {
            debugging("Class not found: $class", DEBUG_DEVELOPER);
            throw new \moodle_exception('classnotfound', 'mod_moodlechatbot', '', $class);
        }
        $reflection = new \ReflectionClass($class);
        if (!$reflection->isSubclassOf('mod_moodlechatbot\tool')) {
            debugging("Class $class does not extend mod_moodlechatbot\\tool", DEBUG_DEVELOPER);
            throw new \moodle_exception('invalidtoolclass', 'mod_moodlechatbot', '', $class);
        }
        return new $class();
    }

    public function execute_tool($name, $params = []) {
        debugging("Executing tool: $name", DEBUG_DEVELOPER);
        try {
            $tool = $this->get_tool($name);
            $result = $tool->execute($params);
            debugging("Tool execution result: " . json_encode($result), DEBUG_DEVELOPER);
            return $result;
        } catch (\Exception $e) {
            debugging("Error executing tool $name: " . $e->getMessage(), DEBUG_DEVELOPER);
            throw new \moodle_exception('toolexecutionerror', 'mod_moodlechatbot', '', $name . ': ' . $e->getMessage());
        }
    }
}
