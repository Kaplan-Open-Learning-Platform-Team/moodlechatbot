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
 * Library of interface functions and constants.
 *
 * @package     mod_moodlechatbot
 * @copyright   2024 Your Name <your@email.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Return if the plugin supports $feature.
 *
 * @param string $feature Constant representing the feature.
 * @return true | null True if the feature is supported, null otherwise.
 */
function moodlechatbot_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
            return true;
        case FEATURE_SHOW_DESCRIPTION:
            return true;
        case FEATURE_GRADE_HAS_GRADE:
            return false;
        case FEATURE_BACKUP_MOODLE2:
            return true;
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_moodlechatbot into the database.
 *
 * @param object $moodlechatbot An object from the form.
 * @param mod_moodlechatbot_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function moodlechatbot_add_instance($moodlechatbot, $mform = null) {
    global $DB;

    $moodlechatbot->timecreated = time();

    $id = $DB->insert_record('moodlechatbot', $moodlechatbot);

    return $id;
}

/**
 * Updates an instance of the mod_moodlechatbot in the database.
 *
 * @param object $moodlechatbot An object from the form in mod_form.php.
 * @param mod_moodlechatbot_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function moodlechatbot_update_instance($moodlechatbot, $mform = null) {
    global $DB;

    $moodlechatbot->timemodified = time();
    $moodlechatbot->id = $moodlechatbot->instance;

    return $DB->update_record('moodlechatbot', $moodlechatbot);
}

/**
 * Removes an instance of the mod_moodlechatbot from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function moodlechatbot_delete_instance($id) {
    global $DB;

    $moodlechatbot = $DB->get_record('moodlechatbot', array('id' => $id));
    if (!$moodlechatbot) {
        return false;
    }

    // Delete any dependent records here.
    $DB->delete_records('moodlechatbot_messages', array('chatbotid' => $moodlechatbot->id));

    $DB->delete_records('moodlechatbot', array('id' => $moodlechatbot->id));

    return true;
}

/**
 * Returns the lists of all browsable file areas within the given module context.
 *
 * @param stdClass $course
 * @param stdClass $cm
 * @param stdClass $context
 * @return string[].
 */
function moodlechatbot_get_file_areas($course, $cm, $context) {
    return array();
}

/**
 * Serves the files from the mod_moodlechatbot file areas.
 *
 * @param stdClass $course The course object.
 * @param stdClass $cm The course module object.
 * @param stdClass $context The mod_moodlechatbot's context.
 * @param string $filearea The name of the file area.
 * @param array $args Extra arguments (itemid, path).
 * @param bool $forcedownload Whether or not force download.
 * @param array $options Additional options affecting the file serving.
 * @return bool False if file not found, does not return if found - just send the file.
 */
function moodlechatbot_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options=array()) {
    // No files are associated with this plugin.
    return false;
}

/**
 * Extends the global navigation tree by adding mod_moodlechatbot nodes if there is a relevant content.
 *
 * This can be called by an AJAX request so do not rely on $PAGE as it might not be set up properly.
 *
 * @param navigation_node $moodlechatbotnode An object representing the navigation tree node.
 * @param stdClass $course
 * @param stdClass $module
 * @param cm_info $cm
 */
function moodlechatbot_extend_navigation($moodlechatbotnode, $course, $module, $cm) {
}

/**
 * Extends the settings navigation with the mod_moodlechatbot settings.
 *
 * This function is called when the context for the page is a mod_moodlechatbot module.
 * This is not called by AJAX so it is safe to rely on the $PAGE.
 *
 * @param settings_navigation $settingsnav Complete settings navigation tree
 * @param navigation_node $moodlechatbotnode mod_moodlechatbot administration node
 */
function moodlechatbot_extend_settings_navigation($settingsnav, $moodlechatbotnode = null) {
}
