<?php


defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");

class mod_moodlechatbot_external extends external_api {
    
    /**
     * Returns description of get_enrolled_courses parameters.
     *
     * @return external_function_parameters
     */
    public static function get_enrolled_courses_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * Get enrolled courses for the current user.
     *
     * @return array of courses
     */
    public static function get_enrolled_courses() {
        global $USER, $DB;

        // Context validation
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        // Capability check
        require_capability('moodle/course:view', $context);

        $courses = enrol_get_my_courses();
        $result = [];

        foreach ($courses as $course) {
            $result[] = [
                'id' => $course->id,
                'fullname' => $course->fullname,
                'shortname' => $course->shortname
            ];
        }

        return $result;
    }

    /**
     * Returns description of get_enrolled_courses result value.
     *
     * @return external_description
     */
    public static function get_enrolled_courses_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'course id'),
                'fullname' => new external_value(PARAM_TEXT, 'course full name'),
                'shortname' => new external_value(PARAM_TEXT, 'course short name')
            ])
        );
    }
}
?>