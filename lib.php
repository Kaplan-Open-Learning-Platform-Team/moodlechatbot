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

    // Check if the user has the capability to view courses
    $usercontext = context_user::instance($userid);
    if (!has_capability('moodle/course:view', $usercontext)) {
        throw new moodle_exception('nopermissions', 'error', '', 'view courses');
    }

    // Use Moodle's internal API to fetch enrolled courses
    $courses = enrol_get_users_courses($userid, true, null, 'visible DESC, sortorder ASC');
    
    // Prepare an array to return course names and IDs
    $courselist = array();
    foreach ($courses as $course) {
        $courselist[] = array(
            'id' => $course->id,
            'fullname' => $course->fullname
        );
    }
    return $courselist;
}

/**
 * AJAX callback to get enrolled courses for the currently logged-in user.
 *
 * This function is designed to be called via an AJAX request.
 */
function mod_moodlechatbot_ajax_get_enrolled_courses() {
    global $USER;

    // Check if the user is logged in and authorized
    require_sesskey();

    // Call the function to fetch the enrolled courses for the current user
    $courses = mod_moodlechatbot_get_enrolled_courses($USER->id);

    // Return the courses as a JSON response
    echo json_encode($courses);
    die(); // Ensure no further output
}

/**
 * Register the AJAX service for fetching enrolled courses.
 */
function moodlechatbot_extend_navigation(navigation_node $navref, stdClass $course, stdClass $module, context_module $context) {
    global $PAGE;

    // This function registers the mod_moodlechatbot_ajax_get_enrolled_courses function as an AJAX callable method.
    $PAGE->requires->js_call_amd('mod_moodlechatbot/courses', 'init', array(
        'ajaxurl' => new moodle_url('/mod/moodlechatbot/ajax.php'),
        'sesskey' => sesskey(),
    ));
}
