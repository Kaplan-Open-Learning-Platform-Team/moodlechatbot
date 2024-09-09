<?php
//access.php
// This file defines the capabilities for the Moodle Chatbot plugin.
defined('MOODLE_INTERNAL') || die();

$capabilities = [
    'mod/moodlechatbot:viewuserenrollments' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => [
            'student' => CAP_ALLOW,  // Add this line to allow students
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ]
    ]
];
?>
