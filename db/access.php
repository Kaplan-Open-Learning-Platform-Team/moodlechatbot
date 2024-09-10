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
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ]
    ],
    'mod/moodlechatbot:viewownenrollments' => [
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'user' => CAP_ALLOW
        ]
    ]
];
?>
