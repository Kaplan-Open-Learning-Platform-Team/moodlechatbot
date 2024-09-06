// db/access.php
<?php
defined('MOODLE_INTERNAL') || die();

$capabilities = array(
    'mod/moodlechatbot:view' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM, // Use CONTEXT_SYSTEM for global access
        'archetypes' => array(
            'student' => CAP_ALLOW,  // Grant access to students
            'teacher' => CAP_ALLOW,  // Grant access to teachers
        ),
    ),
);
