<?php
namespace mod_moodlechatbot\tools;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../tool.php');

class get_upcoming_assignments extends \mod_moodlechatbot\tool {
    public function execute($params = []): array {
        global $USER, $DB;
    
        debugging('Starting execution of get_upcoming_assignments tool', DEBUG_DEVELOPER);
        debugging('Input params: ' . print_r($params, true), DEBUG_DEVELOPER);
    
        try {
            $userid = $params['userid'] ?? $USER->id;
            $timeframe = $params['timeframe'] ?? 'all';
            debugging('Using user ID: ' . $userid . ' and timeframe: ' . $timeframe, DEBUG_DEVELOPER);
            
            // Calculate time range based on timeframe
            $now = time();
            $endtime = match($timeframe) {
                'week' => $now + WEEKSECS,
                'month' => $now + (WEEKSECS * 4),
                'year' => $now + (WEEKSECS * 52),
                default => $now + (WEEKSECS * 52 * 2)
            };
            
            // Get user's courses
            debugging('Retrieving enrolled courses', DEBUG_DEVELOPER);
            $courses = enrol_get_users_courses($userid, true);
            debugging('Found courses: ' . print_r($courses, true), DEBUG_DEVELOPER);
            
            if (empty($courses)) {
                debugging('No enrolled courses found', DEBUG_DEVELOPER);
                return [
                    'success' => true,
                    'message' => 'No enrolled courses found',
                    'current_time' => [
                        'timestamp' => $now,
                        'formatted' => userdate($now)
                    ],
                    'assignments' => []
                ];
            }
            
            // Get the module ID for assignments
            $assignmodule = $DB->get_record('modules', ['name' => 'assign']);
            if (!$assignmodule) {
                debugging('Assignment module not found', DEBUG_DEVELOPER);
                throw new \moodle_exception('Assignment module not found');
            }
            debugging('Found assign module: ' . print_r($assignmodule, true), DEBUG_DEVELOPER);
            
            $courseids = array_keys($courses);
            debugging('Course IDs: ' . implode(',', $courseids), DEBUG_DEVELOPER);
            
            // Build query conditions
            $params = [
                'moduleid' => $assignmodule->id,
                'now' => $now
            ];
            
            // Simplified SQL query
            $sql = "SELECT cm.id, cm.instance, a.name, a.duedate, c.fullname as coursename
                   FROM {course_modules} cm
                   JOIN {assign} a ON a.id = cm.instance
                   JOIN {course} c ON c.id = cm.course
                   WHERE cm.module = :moduleid 
                   AND cm.course IN (" . implode(',', $courseids) . ")
                   AND a.duedate > :now";
            
            if ($timeframe !== 'all') {
                $sql .= " AND a.duedate <= :endtime";
                $params['endtime'] = $endtime;
            }
            
            $sql .= " ORDER BY a.duedate ASC";
            
            debugging('Executing SQL query: ' . $sql, DEBUG_DEVELOPER);
            debugging('Query params: ' . print_r($params, true), DEBUG_DEVELOPER);
            
            $assignments = $DB->get_records_sql($sql, $params);
            debugging('Found assignments: ' . print_r($assignments, true), DEBUG_DEVELOPER);
            
            $result = [];
            foreach ($assignments as $assignment) {
                // Calculate days and hours until due
                $daysUntilDue = ceil(($assignment->duedate - $now) / DAYSECS);
                $hoursUntilDue = ceil(($assignment->duedate - $now) / HOURSECS);
                
                $result[] = [
                    'id' => (int)$assignment->id,
                    'name' => (string)$assignment->name,
                    'course' => (string)$assignment->coursename,
                    'duedate' => (int)$assignment->duedate,
                    'duedateformatted' => userdate($assignment->duedate),
                    'days_until_due' => (int)$daysUntilDue,
                    'hours_until_due' => (int)$hoursUntilDue
                ];
            }
            
            $response = [
                'success' => true,
                'message' => 'Found ' . count($result) . ' upcoming assignments',
                'current_time' => [
                    'timestamp' => $now,
                    'formatted' => userdate($now)
                ],
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
        } finally {
            debugging('Finished execution of get_upcoming_assignments tool', DEBUG_DEVELOPER);
        }
    }
}
