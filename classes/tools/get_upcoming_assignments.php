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
            $timeframe = $params['timeframe'] ?? 'month'; // Options: week, month, all
            debugging('Using user ID: ' . $userid . ' and timeframe: ' . $timeframe, DEBUG_DEVELOPER);
            
            // Calculate time range based on timeframe
            $now = time();
            $endtime = match($timeframe) {
                'week' => $now + WEEKSECS,
                'month' => $now + (WEEKSECS * 4),
                default => 0 // 0 means no end time limit
            };
            
            // Get user's courses
            debugging('Retrieving enrolled courses', DEBUG_DEVELOPER);
            $courses = enrol_get_users_courses($userid, true);
            $courseids = array_keys($courses);
            
            // Build query conditions
            $params = ['modulename' => 'assign', 'userid' => $userid];
            $timecondition = $endtime > 0 ? 'AND duedate > :now AND duedate <= :endtime' : 'AND duedate > :now';
            if ($endtime > 0) {
                $params['endtime'] = $endtime;
            }
            $params['now'] = $now;
            
            // Get assignments
            $sql = "SELECT cm.id, cm.instance, a.name, a.duedate, c.fullname as coursename
                   FROM {course_modules} cm
                   JOIN {assign} a ON a.id = cm.instance
                   JOIN {course} c ON c.id = cm.course
                   JOIN {modules} m ON m.id = cm.module
                   WHERE m.name = :modulename 
                   AND cm.course IN (" . implode(',', $courseids) . ")
                   " . $timecondition . "
                   ORDER BY a.duedate ASC";
            
            debugging('Executing SQL query for assignments', DEBUG_DEVELOPER);
            $assignments = $DB->get_records_sql($sql, $params);
            
            $result = [];
            foreach ($assignments as $assignment) {
                // Calculate days until due
                $daysUntilDue = ceil(($assignment->duedate - $now) / DAYSECS);
                $hoursUntilDue = ceil(($assignment->duedate - $now) / HOURSECS);
                
                $result[] = [
                    'id' => (int)$assignment->id,
                    'name' => (string)$assignment->name,
                    'course' => (string)$assignment->coursename,
                    'duedate' => (int)$assignment->duedate,
                    'duedateformatted' => userdate($assignment->duedate),
                    'days_until_due' => (int)$daysUntilDue,
                    'hours_until_due' => (int)$hoursUntilDue,
                    'relative_time' => $this->get_relative_time_string($daysUntilDue, $hoursUntilDue)
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

    private function get_relative_time_string($days, $hours) {
        if ($days > 30) {
            $months = floor($days / 30);
            return "due in about $months month" . ($months > 1 ? 's' : '');
        } elseif ($days > 7) {
            $weeks = floor($days / 7);
            return "due in about $weeks week" . ($weeks > 1 ? 's' : '');
        } elseif ($days > 1) {
            return "due in $days days";
        } elseif ($days == 1) {
            return "due tomorrow";
        } elseif ($hours > 1) {
            return "due in $hours hours";
        } elseif ($hours == 1) {
            return "due in 1 hour";
        } else {
            return "due very soon";
        }
    }
}
