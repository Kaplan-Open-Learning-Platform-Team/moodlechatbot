// classes/tools/get_enrolled_courses.php

namespace mod_moodlechatbot\tools;

defined('MOODLE_INTERNAL') || die();

class get_enrolled_courses extends \mod_moodlechatbot\tool {
    public function execute($params = []) {
        global $USER, $DB;

        $courses = enrol_get_users_courses($USER->id, true, 'id, shortname, fullname');
        $result = [];

        foreach ($courses as $course) {
            $result[] = [
                'id' => $course->id,
                'shortname' => $course->shortname,
                'fullname' => $course->fullname
            ];
        }

        return $result;
    }
}
