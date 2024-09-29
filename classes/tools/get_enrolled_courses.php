<?php

namespace mod_moodlechatbot\	ools;

defined('MOODLE_INTERNAL') || die();

class get_enrolled_courses extends \\mod_moodlechatbot\	ool {
    public function execute($params = []) {
        global $DB;

        if (empty($params['userid'])) {
            return ['error' => 'User ID is required'];
        }

        $userid = $params['userid'];
        $courses = enrol_get_users_courses($userid, true, 'id, shortname, fullname');
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


