<?php
namespace mod_moodlechatbot\tools;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../tool.php');

class get_enrolled_courses extends \mod_moodlechatbot\tool {
    public function execute(array $params = []): array {
        global $USER, $DB;
    
        try {
            $userid = $params['userid'] ?? $USER->id;
    
            $courses = enrol_get_users_courses($userid, true, 'id, shortname, fullname');
            
            $result = [];
            foreach ($courses as $course) {
                $result[] = [
                    'id' => (int)$course->id,
                    'shortname' => (string)$course->shortname,
                    'fullname' => (string)$course->fullname
                ];
            }
    
            return [
                'success' => true,
                'message' => 'Found ' . count($result) . ' courses',
                'courses' => $result
            ];
    
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error retrieving courses',
                'error' => $e->getMessage(),
                'courses' => []
            ];
        }
    }
}