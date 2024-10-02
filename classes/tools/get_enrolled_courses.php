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
 * @copyright  2024 Your Name &lt;your@email.com&gt;
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_moodlechatbot\tools;

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/../tool.php');

class get_enrolled_courses extends \mod_moodlechatbot\tool {
    public function execute($params = []) {
        global $USER, $DB;

        if (empty($params['userid'])) {
            $userid = $USER->id;
        } else {
            $userid = $params['userid'];
        }

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
