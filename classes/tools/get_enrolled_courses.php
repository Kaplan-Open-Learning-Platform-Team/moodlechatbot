<?php
namespace mod_moodlechatbot\tools;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../tool.php');

class get_enrolled_courses extends \mod_moodlechatbot\tool {
    public function execute(array $params = []): array {
        global $USER, $DB;
    
        \mod_moodlechatbot\debug_to_console("Starting get_enrolled_courses execution");
    
        try {
            $userid = $params['userid'] ?? $USER->id;
            \mod_moodlechatbot\debug_to_console("Using user ID: " . $userid);
    
            $courses = enrol_get_users_courses($userid, true, 'id, shortname, fullname');
            
            \mod_moodlechatbot\debug_to_console("Found " . count($courses) . " courses");
    
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
            \mod_moodlechatbot\debug_to_console("Error in get_enrolled_courses: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Error retrieving courses',
                'error' => $e->getMessage(),
                'courses' => []
            ];
        }
    }
}