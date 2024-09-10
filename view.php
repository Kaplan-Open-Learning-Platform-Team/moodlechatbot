<?php
// view.php

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

// Course module id.
$id = optional_param('id', 0, PARAM_INT);
$m = optional_param('m', 0, PARAM_INT);
$action = optional_param('action', '', PARAM_TEXT); // New parameter for AJAX actions

if ($id) {
    $cm = get_coursemodule_from_id('moodlechatbot', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $cm->course), '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('moodlechatbot', array('id' => $cm->instance), '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('moodlechatbot', array('id' => $m), '*', MUST_EXIST);
    $course = $DB->get_record('course', array('id' => $moduleinstance->course), '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('moodlechatbot', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);

$PAGE->set_url('/mod/moodlechatbot/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

// Handle AJAX request for 'get_courses' action
if ($action === 'get_courses') {
    global $USER;

    // Get the courses the user is enrolled in
    $courses = enrol_get_users_courses($USER->id, true);

    // Prepare a response array to send course names
    $courses_list = array_map(function($course) {
        return $course->fullname;
    }, $courses);

    // Send the response in JSON format
    header('Content-Type: application/json');
    echo json_encode($courses_list);
    exit;
}

echo $OUTPUT->header();

echo html_writer::start_tag('div', array('id' => 'moodlechatbot-container'));
echo html_writer::tag('div', '', array('id' => 'moodlechatbot-messages'));
echo html_writer::start_tag('div', array('id' => 'moodlechatbot-input'));
echo html_writer::tag('textarea', '', array('id' => 'moodlechatbot-textarea', 'placeholder' => 'Type your message here...'));
echo html_writer::tag('button', 'Send', array('id' => 'moodlechatbot-send'));
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

// Include the JavaScript module
$PAGE->requires->js_call_amd('mod_moodlechatbot/interface', 'init');

echo $OUTPUT->footer();
