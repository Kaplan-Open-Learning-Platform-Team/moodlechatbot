<?php
namespace mod_moodlechatbot\tools;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../tool.php');

class get_enrolled_courses extends \mod_moodlechatbot\tool {
    public function execute($params = []) {
        global $USER, $CFG;

        $path = $CFG->dirroot . '/mod/moodlechatbot/classes/helper_functions.php';
        if (file_exists($path)) {
            require_once($path);
        } else {
            debug_to_console(['error' => 'Helper file not found', 'path' => $path]);
        }

        if (empty($params['userid'])) {
            $userid = $USER->id;
            debug_to_console(["message" => "Using current user ID", "userid" => $userid]);
        } else {
            $userid = $params['userid'];
            debug_to_console(["message" => "Using provided user ID", "userid" => $userid]);
        }

        $courses = enrol_get_users_courses($userid, true, 'id, shortname, fullname');

        if ($courses === false) {
            debug_to_console(["error" => "Error retrieving courses", "userid" => $userid]);
            return ['courses' => [], 'debug' => get_debug_log()];
        }

        debug_to_console(["message" => "Retrieved courses", "count" => count($courses)]);

        $result = [];
        foreach ($courses as $course) {
            $result[] = [
                'id' => $course->id,
                'shortname' => $course->shortname,
                'fullname' => $course->fullname
            ];
        }

        debug_to_console(["message" => "Processed courses", "courses" => $result]);

        return [
            'courses' => $result,
            'debug' => get_debug_log()
        ];
    }
}