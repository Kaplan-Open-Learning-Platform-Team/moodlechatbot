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
 * The main mod_moodlechatbot configuration form.
 *
 * @package     mod_moodlechatbot
 * @copyright   2024 Your Name <your@email.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot.'/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package    mod_moodlechatbot
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_moodlechatbot_mod_form extends moodleform_mod {

    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('moodlechatbotname', 'mod_moodlechatbot'), array('size' => '64'));
        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'moodlechatbotname', 'mod_moodlechatbot');

        // Adding the standard "intro" and "introformat" fields.
        if ($CFG->branch >= 29) {
            $this->standard_intro_elements();
        } else {
            $this->add_intro_editor();
        }

        // Adding the rest of mod_moodlechatbot settings, spreading all them into this fieldset
        // or adding more fieldsets ('header' elements) if needed for better logic.
        $mform->addElement('static', 'label1', 'moodlechatbotsettings', get_string('moodlechatbotsettings', 'mod_moodlechatbot'));

        $mform->addElement('header', 'moodlechatbotfieldset', get_string('moodlechatbotfieldset', 'mod_moodlechatbot'));

        // Add custom fields here.
        // For example, if you want to add a field for the maximum number of messages:
        $mform->addElement('text', 'maxmessages', get_string('maxmessages', 'mod_moodlechatbot'), array('size' => '4'));
        $mform->setType('maxmessages', PARAM_INT);
        $mform->setDefault('maxmessages', 50); // Set a default value
        $mform->addHelpButton('maxmessages', 'maxmessages', 'mod_moodlechatbot');

        // Add standard grading elements.
        $this->standard_grading_coursemodule_elements();

        // Add standard elements, common to all modules.
        $this->standard_coursemodule_elements();

        // Add standard buttons, common to all modules.
        $this->add_action_buttons();
    }
}
