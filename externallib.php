<?php

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

/**
 * Class to define external functions for the Moodle chatbot.
 */
class mod_moodlechatbot_external extends external_api {

    /**
     * Define the parameters for the external function get_enrolled_courses.
     *
     * @return external_function_parameters
     */
    public static function get_enrolled_courses_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * Fetch and return the courses a user is enrolled in.
     *
     * @return array List of enrolled courses.
     * @throws moodle_exception
     */
    public static function get_enrolled_courses() {
        global $USER, $DB;

        // Check for cached data (optional, if caching is implemented)
        $cache = cache::make('mod_moodlechatbot', 'moodlechatbot_courses');
        $cachedcourses = $cache->get($USER->id);

        if ($cachedcourses !== false) {
            return $cachedcourses; // Return cached courses if available
        }

        // Fetch enrolled courses for the current user
        $courses = enrol_get_users_courses($USER->id, true, 'id, fullname');
        
        // Store the result in the cache
        $cache->set($USER->id, $courses);

        // Return the list of courses
        return array_values($courses);
    }

    /**
     * Define the return structure for the get_enrolled_courses function.
     *
     * @return external_multiple_structure
     */
    public static function get_enrolled_courses_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                array(
                    'id' => new external_value(PARAM_INT, 'Course ID'),
                    'fullname' => new external_value(PARAM_TEXT, 'Course Fullname')
                )
            )
        );
    }
}
