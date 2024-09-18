<?php
namespace yourpluginname\external;

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;

class get_enrolled_courses extends external_api {

    // Define the parameters accepted by the external function.
    public static function get_enrolled_courses_parameters() {
        return new external_function_parameters(
            []
        );
    }

    // The function that performs the actual logic of retrieving the user's enrolled courses.
    public static function get_enrolled_courses() {
        global $USER;

        // Fetch the user's enrolled courses.
        $courses = enrol_get_users_courses($USER->id);

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

    // Define the return structure of the external function.
    public static function get_enrolled_courses_returns() {
        return new external_multiple_structure(
            new external_single_structure(
                [
                    'id' => new external_value(PARAM_INT, 'Course ID'),
                    'fullname' => new external_value(PARAM_TEXT, 'Full course name'),
                    'shortname' => new external_value(PARAM_TEXT, 'Short course name'),
                ]
            )
        );
    }
}
?>
