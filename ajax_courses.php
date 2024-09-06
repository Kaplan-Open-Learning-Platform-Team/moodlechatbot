// ajax_courses.php
require_once('../../config.php');
require_once('lib.php');

// Require the user to be logged in.
require_login();

// Get the current user's id
$userid = $USER->id;

// Get enrolled courses for the user
$courses = mod_moodlechatbot_get_enrolled_courses($userid);

// Return the list of courses as JSON
echo json_encode($courses);
