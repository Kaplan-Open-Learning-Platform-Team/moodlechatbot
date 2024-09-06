defined('MOODLE_INTERNAL') || die();

require_once("$CFG->libdir/externallib.php");
require_once($CFG->dirroot.'/mod/moodlechatbot/lib.php'); // Include lib.php where the course fetching logic is defined

class mod_moodlechatbot_external extends external_api {

    // Parameters definition for the function
    public static function get_enrolled_courses_parameters() {
        return new external_function_parameters([]);
    }

    // The function to fetch enrolled courses
    public static function get_enrolled_courses() {
        global $USER;

        // Validate parameters.
        self::validate_parameters(self::get_enrolled_courses_parameters(), []);

        // Context validation.
        $context = context_system::instance(); // Can be CONTEXT_SYSTEM or CONTEXT_MODULE
        self::validate_context($context);

        // Capability checking (optional, if you have implemented permissions in access.php)
        // require_capability('mod/moodlechatbot:view', $context);

        // Call the function from lib.php to get the courses
        $courses = mod_moodlechatbot_get_enrolled_courses($USER->id);

        // Return the courses list
        return $courses;
    }

    // Return structure for the function (defines the format of the response)
    public static function get_enrolled_courses_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Course ID'),
                'fullname' => new external_value(PARAM_TEXT, 'Course full name'),
            ])
        );
    }
}
