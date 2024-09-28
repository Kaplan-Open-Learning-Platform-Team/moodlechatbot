// classes/tool.php

namespace mod_moodlechatbot;

defined('MOODLE_INTERNAL') || die();

abstract class tool {
    abstract public function execute($params = []);
}
