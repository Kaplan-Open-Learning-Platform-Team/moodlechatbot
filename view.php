<?php
// view.php

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once(__DIR__.'/classes/helper_functions.php');

// Test logging at the start of the file
\mod_moodlechatbot\debug_helper::log("View.php started");

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$m = optional_param('m', 0, PARAM_INT);

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

// Test logging after page setup
\mod_moodlechatbot\debug_helper::log("Page setup completed");

// Handle log clearing
if (optional_param('clear_logs', false, PARAM_BOOL) && confirm_sesskey()) {
    \mod_moodlechatbot\debug_helper::clear_logs();
    \mod_moodlechatbot\debug_helper::log("Logs cleared");
    redirect($PAGE->url);
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

// Test logging before displaying debug logs
\mod_moodlechatbot\debug_helper::log("About to display debug logs");

// Display debug logs
if (debugging()) {
    echo html_writer::tag('h3', 'Debug Logs');
    
    // Add Clear Logs button
    $clear_url = new moodle_url($PAGE->url, array('clear_logs' => 1, 'sesskey' => sesskey()));
    echo html_writer::link($clear_url, 'Clear Logs', array('class' => 'btn btn-secondary'));
    
    \mod_moodlechatbot\debug_helper::display_logs();
}

// Test logging at the end of the file
\mod_moodlechatbot\debug_helper::log("View.php completed");

echo $OUTPUT->footer();
