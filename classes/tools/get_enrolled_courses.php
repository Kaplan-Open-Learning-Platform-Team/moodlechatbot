<?php
namespace mod_moodlechatbot\tools;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../tool.php');

class get_enrolled_courses extends \mod_moodlechatbot\tool {
    public function execute($params = []): array {
        global $USER, $DB;
    
        debugging('Starting execution of get_enrolled_courses tool', DEBUG_DEVELOPER);
        debugging('Input params: ' . print_r($params, true), DEBUG_DEVELOPER);
    
        try {
            $userid = $params['userid'] ?? $USER->id;
            debugging('Using user ID: ' . $userid, DEBUG_DEVELOPER);
    
            debugging('Calling enrol_get_users_courses function', DEBUG_DEVELOPER);
            $courses = enrol_get_users_courses($userid, true, 'id, shortname, fullname');
            debugging('Found ' . count($courses) . ' courses', DEBUG_DEVELOPER);
            
            $result = [];
            foreach ($courses as $course) {
                $result[] = [
                    'id' => (int)$course->id,
                    'shortname' => (string)$course->shortname,
                    'fullname' => (string)$course->fullname
                ];
            }
    
            $response = [
                'success' => true,
                'message' => 'Found ' . count($result) . ' courses',
                'courses' => $result
            ];
            debugging('Prepared response: ' . print_r($response, true), DEBUG_DEVELOPER);
            return $response;
    
        } catch (\Exception $e) {
            debugging('Error in get_enrolled_courses: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), DEBUG_DEVELOPER);
            return [
                'success' => false,
                'message' => 'Error retrieving courses',
                'error' => $e->getMessage(),
                'courses' => []
            ];
        } finally {
            debugging('Finished execution of get_enrolled_courses tool', DEBUG_DEVELOPER);
        }
    }
}
