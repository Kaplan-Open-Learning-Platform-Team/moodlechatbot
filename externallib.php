// externallib.php
require_once($CFG->dirroot.'/mod/moodlechatbot/lib.php');

defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot.'/mod/moodlechatbot/lib.php'); // Include lib.php

class mod_moodlechatbot_external extends external_api {
    public static function get_enrolled_courses_parameters() {
        return new external_function_parameters([]);
    }

    public static function get_enrolled_courses() {
        global $USER;
        return mod_moodlechatbot_get_enrolled_courses($USER->id);
    }

    public static function get_enrolled_courses_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Course ID'),
                'fullname' => new external_value(PARAM_TEXT, 'Course full name'),
            ])
        );
    }
}
