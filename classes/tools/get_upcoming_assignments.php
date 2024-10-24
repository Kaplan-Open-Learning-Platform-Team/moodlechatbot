<?php
namespace mod_moodlechatbot\tools;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../tool.php');

class get_upcoming_assignments extends \mod_moodlechatbot\tool {
    public function execute($params = []): array {
        global $USER, $DB;
    
        debugging('Starting execution of get_upcoming_assignments tool', DEBUG_DEVELOPER);
        
        try {
            $userid = $params['userid'] ?? $USER->id;
            $now = time();
            
            // Get user's courses
            $courses = enrol_get_users_courses($userid, true);
            if (empty($courses)) {
                debugging('No enrolled courses found', DEBUG_DEVELOPER);
                return [
                    'success' => true,
                    'message' => 'No enrolled courses found',
                    'current_time' => $now,
                    'assignments' => []
                ];
            }
            
            // Get the module ID for assignments
            $assignmodule = $DB->get_record('modules', ['name' => 'assign']);
            if (!$assignmodule) {
                debugging('Assignment module not found', DEBUG_DEVELOPER);
                throw new \moodle_exception('Assignment module not found');
            }
            
            $courseids = array_keys($courses);
            
            // Get all future assignments
            $sql = "SELECT cm.id, cm.instance, a.name, a.duedate, c.fullname as coursename
                   FROM {course_modules} cm
                   JOIN {assign} a ON a.id = cm.instance
                   JOIN {course} c ON c.id = cm.course
                   WHERE cm.module = :moduleid 
                   AND cm.course IN (" . implode(',', $courseids) . ")
                   AND a.duedate > :now
                   ORDER BY a.duedate ASC";
            
            $params = [
                'moduleid' => $assignmodule->id,
                'now' => $now
            ];
            
            debugging('Executing SQL query: ' . $sql, DEBUG_DEVELOPER);
            $assignments = $DB->get_records_sql($sql, $params);
            debugging('Found assignments: ' . print_r($assignments, true), DEBUG_DEVELOPER);
            
            $result = [];
            foreach ($assignments as $assignment) {
                $daysUntilDue = ceil(($assignment->duedate - $now) / DAYSECS);
                
                $result[] = [
                    'name' => $assignment->name,
                    'course' => $assignment->coursename,
                    'duedate' => (int)$assignment->duedate,
                    'days_until_due' => (int)$daysUntilDue
                ];
            }
            
            $response = [
                'success' => true,
                'current_time' => $now,
                'assignments' => $result
            ];
            
            debugging('Prepared response: ' . print_r($response, true), DEBUG_DEVELOPER);
            return $response;
            
        } catch (\Exception $e) {
            debugging('Error in get_upcoming_assignments: ' . $e->getMessage() . "\n" . $e->getTraceAsString(), DEBUG_DEVELOPER);
            return [
                'success' => false,
                'message' => 'Error retrieving assignments',
                'error' => $e->getMessage(),
                'assignments' => []
            ];
        }
    }
}
