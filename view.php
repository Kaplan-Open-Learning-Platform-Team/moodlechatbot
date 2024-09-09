<?php
//view.php
require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once($CFG->dirroot.'/enrol/locallib.php');

// Course module id.
$id = optional_param('id', 0, PARAM_INT);
// Activity instance id.
$m = optional_param('m', 0, PARAM_INT);

// AJAX request handling for course enrollment
function handle_ajax_request() {
    global $USER, $DB;
    
    // Ensure the user is logged in.
    require_login();

    // Function to retrieve the user's enrolled courses.
    function get_user_courses($userid) {
        global $DB;
        $courses = enrol_get_users_courses($userid);
        if ($courses) {
            foreach ($courses as &$course) {
                $course->fullname = $DB->get_field('course', 'fullname', ['id' => $course->id]);
            }
        }
        return $courses;
    }

    // Check for course module or instance
    $id = optional_param('id', 0, PARAM_INT);
    $m = optional_param('m', 0, PARAM_INT);
    
    if ($id) {
        $cm = get_coursemodule_from_id('moodlechatbot', $id, 0, false, MUST_EXIST);
        $modulecontext = context_module::instance($cm->id);
    } elseif ($m) {
        $moduleinstance = $DB->get_record('moodlechatbot', ['id' => $m], '', MUST_EXIST);
        $cm = get_coursemodule_from_instance('moodlechatbot', $moduleinstance->id, $moduleinstance->course, false, MUST_EXIST);
        $modulecontext = context_module::instance($cm->id);
    } else {
        die("Invalid module or instance ID."); 
    }
    
    // Perform the capability check.
    require_capability('mod/moodlechatbot:viewuserenrollments', $modulecontext);

    // Get user's enrolled courses.
    $courses = get_user_courses($USER->id);
    
    // Return the course names as a JSON response.
    header('Content-Type: application/json');
    $courseNames = $courses ? array_map(function($course) { return $course->fullname; }, $courses) : [];
    echo json_encode(['courses' => $courseNames]);
    exit();
}

if (optional_param('ajax', false, PARAM_BOOL)) {
    handle_ajax_request();
}

// Regular non-AJAX view logic...
if ($id) {
    $cm = get_coursemodule_from_id('moodlechatbot', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '', MUST_EXIST);
    $moduleinstance = $DB->get_record('moodlechatbot', ['id' => $cm->instance], '', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('moodlechatbot', ['id' => $m], '', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('moodlechatbot', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);
$PAGE->set_url('/mod/moodlechatbot/view.php', ['id' => $cm->id]);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

echo $OUTPUT->header();
echo html_writer::start_tag('div', ['id' => 'moodlechatbot-container']);
echo html_writer::tag('div', '', ['id' => 'moodlechatbot-messages']);
echo html_writer::start_tag('div', ['id' => 'moodlechatbot-input']);
echo html_writer::tag('textarea', '', ['id' => 'moodlechatbot-textarea', 'placeholder' => 'Type your message here...']);
echo html_writer::tag('button', 'Send', ['id' => 'moodlechatbot-send']);
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

global $USER; 
$userid = $USER->id;

// Include the JavaScript module and pass the user ID.
$PAGE->requires->js_call_amd('mod_moodlechatbot/interface', 'init', [$userid]);
echo $OUTPUT->footer();
?>
