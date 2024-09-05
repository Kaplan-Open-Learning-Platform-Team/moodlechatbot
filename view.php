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
 * @copyright   2024 Your Name <your@email.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__.'/../../config.php');
require_once(__DIR__.'/lib.php');

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

$event = \mod_moodlechatbot\event\course_module_viewed::create(array(
    'objectid' => $moduleinstance->id,
    'context' => $modulecontext
));
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('moodlechatbot', $moduleinstance);
$event->trigger();

$PAGE->set_url('/mod/moodlechatbot/view.php', array('id' => $cm->id));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

// Start output here.
echo $OUTPUT->header();

echo $OUTPUT->heading(format_string($moduleinstance->name), 2);

if ($moduleinstance->intro) {
    echo $OUTPUT->box(format_module_intro('moodlechatbot', $moduleinstance, $cm->id), 'generalbox mod_introbox', 'moodlechatbotintro');
}

$chatid = 'moodlechatbot-' . uniqid();

echo html_writer::start_tag('div', array(
    'id' => $chatid,
    'class' => 'mod_moodlechatbot_chat',
    'data-chatbotid' => $cm->instance
));
echo html_writer::tag('div', '', array('data-region' => 'messages'));
echo html_writer::start_tag('div', array('class' => 'mod_moodlechatbot_input'));
echo html_writer::tag('textarea', '', array(
    'data-region' => 'input',
    'placeholder' => get_string('typemessage', 'mod_moodlechatbot'),
    'rows' => 3
));
echo html_writer::tag('button', get_string('send', 'mod_moodlechatbot'), array(
    'data-action' => 'send',
    'class' => 'btn btn-primary'
));
echo html_writer::end_tag('div');
echo html_writer::end_tag('div');

$PAGE->requires->js_call_amd('mod_moodlechatbot/chat', 'init', [$chatid]);

echo $OUTPUT->footer();
