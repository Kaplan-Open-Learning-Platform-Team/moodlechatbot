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
 * Moodle Chatbot external API
 *
 * @package    mod_moodlechatbot
 * @category   external
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");

class mod_moodlechatbot_external extends external_api {
    
    /**
     * Returns description of get_enrolled_courses parameters.
     *
     * @return external_function_parameters
     */
    public static function get_enrolled_courses_parameters() {
        return new external_function_parameters([]);
    }

    /**
     * Get enrolled courses for the current user.
     *
     * @return array of courses
     */
    public static function get_enrolled_courses() {
        global $USER, $DB;

        // Context validation
        $context = context_user::instance($USER->id);
        self::validate_context($context);

        // Capability check
        require_capability('moodle/course:view', $context);

        $courses = enrol_get_my_courses();
        $result = [];

        foreach ($courses as $course) {
            $result[] = [
                'id' => $course->id,
                'fullname' => $course->fullname,
                'shortname' => $course->shortname
            ];
        }

        return $result;
    }

    /**
     * Returns description of get_enrolled_courses result value.
     *
     * @return external_description
     */
    public static function get_enrolled_courses_returns() {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'course id'),
                'fullname' => new external_value(PARAM_TEXT, 'course full name'),
                'shortname' => new external_value(PARAM_TEXT, 'course short name')
            ])
        );
    }
}
?>