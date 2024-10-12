<?php
// classes/tool.php

namespace mod_moodlechatbot;

defined('MOODLE_INTERNAL') || die();

abstract class tool {

    public function __construct() {
        // This helps verify the correct tool class is loaded. It is minimal to avoid excessive logging.
        $className = get_class($this);  // Get the name of the instantiated concrete class.
        debugging("Tool class instantiated: " . $className, DEBUG_DEVELOPER);
    }

    abstract public function execute($params = []);
}
