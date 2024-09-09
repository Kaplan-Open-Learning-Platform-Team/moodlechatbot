<?php

// This file defines the capabilities for the Moodle Chatbot plugin.

defined('MOODLE_INTERNAL') || die();

$capabilities = array(

    'mod/moodlechatbot:viewuserenrollments' => array( // Updated component name

        'riskbitmask' => RISK_PERSONAL, 
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        )
    )
);

?>
