<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Get enrolled courses tool for the Moodle Chatbot plugin.
 *
 * @package    mod_moodlechatbot
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_moodlechatbot\tools;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../tool.php');

// Include the helper function (assuming it's in a separate file or defined globally).
// If it's in a separate file, use: require_once('path/to/helper_functions.php');

class get_enrolled_courses extends \mod_moodlechatbot\tool {
    public function execute($params = []) {
        global $USER, $CFG; // Include $USER here

        $path = $CFG->dirroot . '/mod/moodlechatbot/classes/helper_functions.php';

        if (file_exists($path)) {
            require_once($path);
        } else {
             debug_to_console(['Error: helper file not found:' => $path]);
        }

        if (empty($params['userid'])) {
            $userid = $USER->id;
            debug_to_console("Using current user ID: " . $userid);
        } else {
            $userid = $params['userid'];
            debug_to_console("Using provided user ID: " . $userid); 
        }

        $courses = enrol_get_users_courses($userid, true, 'id, shortname, fullname');

        if ($courses === false) {  // Check for errors from enrol_get_users_courses
            debug_to_console("Error retrieving courses for user ID: " . $userid);
            return []; // Or handle the error differently
        }

        debug_to_console("Retrieved courses: " . count($courses)); // Log the number of courses fetched

        $result = [];

        foreach ($courses as $course) {
            $result[] = [
                'id' => $course->id,
                'shortname' => $course->shortname,
                'fullname' => $course->fullname
            ];
        }

        debug_to_console(['Processed courses:' => $result]); // Log the formatted course data


        debug_to_console("Exiting get_enrolled_courses tool"); // Log exit point

        return $result;
    }
}



function debug_to_console($data) {
    $output = $data;
    if (is_array($output) || is_object($output)) { // Handle arrays and objects
        $output = json_encode($output);
    }
    echo "<script>console.log('Debug Objects: " . $output . "' );</script>";
}
?>