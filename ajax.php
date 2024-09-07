<?php
define('AJAX_SCRIPT', true);

require_once('../../config.php');
require_once($CFG->dirroot.'/mod/moodlechatbot/lib.php');

// Ensure the user is logged in and has a valid session
require_login();
require_sesskey();

// Set the content type to JSON
header('Content-Type: application/json');

try {
    $courses = mod_moodlechatbot_ajax_get_enrolled_courses();
    echo json_encode(['success' => true, 'data' => $courses]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
die();
