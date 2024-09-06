// db/services.php
$functions = array(
    'mod_moodlechatbot_get_enrolled_courses' => array(
        'classname'   => 'mod_moodlechatbot_external',
        'methodname'  => 'get_enrolled_courses',
        'classpath'   => 'mod/moodlechatbot/externallib.php',
        'description' => 'Returns a list of courses the current user is enrolled in.',
        'type'        => 'read',
        'ajax'        => true,
        'classpath'   => 'mod/moodlechatbot/ajax_courses.php', // Updated path
    ),
);
