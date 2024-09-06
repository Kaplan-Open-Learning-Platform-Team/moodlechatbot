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
 * @copyright   2024 Kaplan Open Learning <kol-learning-tech@kaplan.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

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
        default:
            return null;
    }
}

/**
 * Saves a new instance of the mod_moodlechatbot into the database.
 *
 * Given an object containing all the necessary data, (defined by the form
 * in mod_form.php) this function will create a new instance and return the id
 * number of the instance.
 *
 * @param object $moduleinstance An object from the form.
 * @param mod_moodlechatbot_mod_form $mform The form.
 * @return int The id of the newly inserted record.
 */
function moodlechatbot_add_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timecreated = time();

    $id = $DB->insert_record('moodlechatbot', $moduleinstance);

    return $id;
}

/**
 * Updates an instance of the mod_moodlechatbot in the database.
 *
 * Given an object containing all the necessary data (defined in mod_form.php),
 * this function will update an existing instance with new data.
 *
 * @param object $moduleinstance An object from the form in mod_form.php.
 * @param mod_moodlechatbot_mod_form $mform The form.
 * @return bool True if successful, false otherwise.
 */
function moodlechatbot_update_instance($moduleinstance, $mform = null) {
    global $DB;

    $moduleinstance->timemodified = time();
    $moduleinstance->id = $moduleinstance->instance;

    return $DB->update_record('moodlechatbot', $moduleinstance);
}

/**
 * Removes an instance of the mod_moodlechatbot from the database.
 *
 * @param int $id Id of the module instance.
 * @return bool True if successful, false on failure.
 */
function moodlechatbot_delete_instance($id) {
    global $DB;

    $exists = $DB->get_record('moodlechatbot', array('id' => $id));
    if (!$exists) {
        return false;
    }

    $DB->delete_records('moodlechatbot', array('id' => $id));

    return true;
}

/**
 * Fetches a list of courses that the specified user is currently enrolled in.
 *
 * This function retrieves all courses in which the provided user ID is enrolled.
 * It uses Moodle's core function enrol_get_users_courses() to ensure that only courses
 * the user is permitted to view are returned. The courses are sorted by visibility and
 * sort order.
 *
 * @param int $userid The ID of the user whose enrolled courses are to be fetched.
 * @return array List of courses the user is enrolled in. Each course entry contains:
 *   - id: The course ID.
 *   - fullname: The full name of the course.
 */
function mod_moodlechatbot_get_enrolled_courses($userid) {
    global $DB;

    // Get courses where the user is enrolled.
    $courses = enrol_get_users_courses($userid, true, null, 'visible DESC, sortorder ASC');

    // Prepare an array to return course names and IDs.
    $courselist = array();
    foreach ($courses as $course) {
        $courselist[] = array(
            'id' => $course->id,
            'fullname' => $course->fullname
        );
    }

    return $courselist;
}

