// db/access.php
$capabilities = array(
    'mod/moodlechatbot:view' => array(
        'captype' => 'read',
        'contextlevel' => CONTEXT_MODULE,
        'archetypes' => array(
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
        ),
    ),
);
