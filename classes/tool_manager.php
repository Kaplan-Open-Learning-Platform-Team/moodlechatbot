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

    public function execute_tool($name, $params = []) {
        $tool = $this->get_tool($name);
        if ($tool) {
            return $tool->execute($params);
        }
        return null;
    }
}

// classes/tool.php

namespace mod_moodlechatbot;

defined('MOODLE_INTERNAL') || die();

abstract class tool {
    abstract public function execute($params = []);
}

// classes/tools/get_enrolled_courses.php

namespace mod_moodlechatbot\tools;

defined('MOODLE_INTERNAL') || die();

class get_enrolled_courses extends \mod_moodlechatbot\tool {
    public function execute($params = []) {
        global $USER, $DB;

        $courses = enrol_get_users_courses($USER->id, true, 'id, shortname, fullname');
        $result = [];

        foreach ($courses as $course) {
            $result[] = [
                'id' => $course->id,
                'shortname' => $course->shortname,
                'fullname' => $course->fullname
            ];
        }

        return $result;
    }
}
