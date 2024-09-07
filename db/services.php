<?php
$functions = array(
    'yourplugin_ajax_get_courses' => array(
        'classname'   => 'yourplugin_external', // You can also define the function in an external class.
        'methodname'  => 'get_courses', // Name of your method.
        'classpath'   => 'local/yourplugin/classes/external.php',
        'description' => 'Get user enrolled courses for chatbot',
        'type'        => 'read',
        'ajax'        => true
    ),
);

