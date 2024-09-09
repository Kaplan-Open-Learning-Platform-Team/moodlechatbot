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
        $courses = enrol_get_users_courses($userid);
        $course_names = array();
        foreach ($courses as $course) {
            $course_names[] = $course->fullname;
        }
        return $course_names;
    }

    // Check for course module or instance
    $id = optional_param('id', 0, PARAM_INT);
    $m = optional_param('m', 0, PARAM_INT);
    
    try {
        if ($id) {
            $cm = get_coursemodule_from_id('moodlechatbot', $id, 0, false, MUST_EXIST);
            $modulecontext = context_module::instance($cm->id);
        } elseif ($m) {
            $moduleinstance = $DB->get_record('moodlechatbot', array('id' => $m), '*', MUST_EXIST);
            $cm = get_coursemodule_from_instance('moodlechatbot', $moduleinstance->id, $moduleinstance->course, false, MUST_EXIST);
            $modulecontext = context_module::instance($cm->id);
        } else {
            throw new moodle_exception('missingparameter');
        }
        
        // Check if the user has permission to view enrollments
        if (has_capability('mod/moodlechatbot:viewuserenrollments', $modulecontext) ||
            has_capability('mod/moodlechatbot:viewownenrollments', context_system::instance())) {
            $courses = get_user_courses($USER->id);
            
            // Return the course names as a JSON response.
            header('Content-Type: application/json');
            echo json_encode(array('courses' => $courses));
        } else {
            throw new moodle_exception('nopermissions', 'error', '', 'view course enrollments');
        }
    } catch (Exception $e) {
        header('HTTP/1.1 500 Internal Server Error');
        echo json_encode(array('error' => $e->getMessage()));
    }
    exit();
}

if (optional_param('ajax', false, PARAM_BOOL)) {
    handle_ajax_request();
}

// Regular non-AJAX view logic...
try {
    if ($id) {
        $cm = get_coursemodule_from_id('moodlechatbot', $id, 0, false, MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
        $moduleinstance = $DB->get_record('moodlechatbot', array('id' => $cm->instance), '*', MUST_EXIST);
    } elseif ($m) {
        $moduleinstance = $DB->get_record('moodlechatbot', array('id' => $m), '*', MUST_EXIST);
        $course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
        $cm = get_coursemodule_from_instance('moodlechatbot', $moduleinstance->id, $course->id, false, MUST_EXIST);
    } else {
        throw new moodle_exception('missingparameter');
    }

    require_login($course, true, $cm);
    $modulecontext = context_module::instance($cm->id);

    $PAGE->set_url('/mod/moodlechatbot/view.php', array('id' => $cm->id));
    $PAGE->set_title(format_string($moduleinstance->name));
    $PAGE->set_heading(format_string($course->fullname));
    $PAGE->set_context($modulecontext);

    echo $OUTPUT->header();
    echo html_writer::start_tag('div', array('id' => 'moodlechatbot-container'));
    echo html_writer::tag('div', '', array('id' => 'moodlechatbot-messages'));
    echo html_writer::start_tag('div', array('id' => 'moodlechatbot-input'));
    echo html_writer::tag('textarea', '', array('id' => 'moodlechatbot-textarea', 'placeholder' => 'Type your message here...'));
    echo html_writer::tag('button', 'Send', array('id' => 'moodlechatbot-send'));
    echo html_writer::end_tag('div');
    echo html_writer::end_tag('div');

    $PAGE->requires->js_call_amd('mod_moodlechatbot/interface', 'init', array($USER->id));

    echo $OUTPUT->footer();

} catch (Exception $e) {
    debugging('Error in view.php: ' . $e->getMessage(), DEBUG_DEVELOPER);
    throw $e;
}
?>
