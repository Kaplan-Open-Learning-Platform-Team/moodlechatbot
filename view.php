<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Prints an instance of mod_moodlechatbot.
 *
 * @package     mod_moodlechatbot
 * @copyright   2024 Kaplan Open Learning <kol-learning-tech@kaplan.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');
require_once($CFG->dirroot.'/enrol/locallib.php');  // ADD THIS LINE

// Course module id.
$id = optional_param('id', 0, PARAM_INT);

// Activity instance id.
$m = optional_param('m', 0, PARAM_INT);

// Check if this is an AJAX request to retrieve courses.
if (optional_param('ajax', false, PARAM_BOOL)) {
    global $USER, $DB;

    // Function to retrieve the user's enrolled courses.
    function get_user_courses($userid) {
        global $DB;
        $courses = enrol_get_users_courses($userid);
        if ($courses) {
            foreach ($courses as &$course) {
                $course->fullname = $DB->get_field('course', 'fullname', array('id' => $course->id));
            }
        }
        return $courses;
    }

    // Ensure the user is logged in.
    require_login();

    // Get the user's enrolled courses.
    $courses = get_user_courses($USER->id);

    // Return the courses as a JSON response.
    header('Content-Type: application/json');
    if ($courses) {
        echo json_encode($courses);
    } else {
        echo json_encode(array('error' => 'No courses found.'));
    }
    exit();
}

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

echo $OUTPUT->header();

echo html_writer::start_tag('div', array('id' => 'moodlechatbot-container'));
echo html_writer::tag('div', '', array('id' => 'moodlechatbot-messages'));
echo html_writer::start_tag('div', array('id' => 'moodlechatbot-input'));
echo html_writer::tag('textarea', '', array('id' => 'moodlechatbot-textarea', 'placeholder' => 'Type your message here...'));
echo html_writer::tag('button', 'Send', array('id' => 'moodlechatbot-send'));
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

global $USER;  // Get the global $USER object to access the current user data.
$userid = $USER->id;  // Capture the user ID.

// Include the JavaScript module and Pass the user ID to your JavaScript file.
$PAGE->requires->js_call_amd('mod_moodlechatbot/interface', 'init', array($userid));

echo $OUTPUT->footer();
