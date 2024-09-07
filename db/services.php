<?php
$functions = array(
    'mod_moodlechatbot_get_enrolled_courses' => array(
        'classname'   => 'mod_moodlechatbot_external',
        'methodname'  => 'get_enrolled_courses',
        'classpath'   => 'mod/moodlechatbot/externallib.php',
        'description' => 'Returns a list of enrolled courses for a given user',
        'type'        => 'read',
        'ajax'        => true,
        'capabilities'=> 'moodle/course:view'
    ),
);

