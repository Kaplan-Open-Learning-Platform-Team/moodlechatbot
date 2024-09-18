$functions = [
    'yourpluginname_get_enrolled_courses' => [
        'classname' => 'yourpluginname\external\get_enrolled_courses',
        'methodname' => 'get_enrolled_courses',
        'classpath' => 'yourpluginname/classes/external/get_enrolled_courses.php',
        'description' => 'Returns the list of courses the user is enrolled in',
        'type' => 'read',
        'ajax' => true, // This makes it available for AJAX calls
        'capabilities' => '',
    ],
];
