<?php

$functions = array(
    'mod_moodlechatbot_get_enrolled_courses' => array(
        'classname'   => 'mod_moodlechatbot_external',
        'methodname'  => 'get_enrolled_courses',
        'classpath'   => 'mod/moodlechatbot/externallib.php',
        'description' => 'Returns a list of courses the user is enrolled in',
        'type'        => 'read',
        'ajax'        => true, // Enable AJAX
        'loginrequired' => true,
        'capabilities' => ''
    ),
);

$services = array(
    'Moodle Chatbot Service' => array(
        'functions' => array('mod_moodlechatbot_get_enrolled_courses'),
        'restrictedusers' => 0,
        'enabled' => 1,
    ),
);
