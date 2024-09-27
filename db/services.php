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
 * Moodle Chatbot external functions and service definitions.
 *
 * @package    mod_moodlechatbot
 * @category   webservice
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_moodlechatbot_get_enrolled_courses' => [
        'classname'   => 'mod_moodlechatbot_external',
        'methodname'  => 'get_enrolled_courses',
        'classpath'   => 'mod/moodlechatbot/classes/external.php',
        'description' => 'Get enrolled courses for the current user',
        'type'        => 'read',
        'ajax'        => true,
    ],
    'mod_moodlechatbot_send_message' => [
        'classname'   => 'mod_moodlechatbot\external\send_message',
        'methodname'  => 'execute',
        'description' => 'Send a message to the chatbot',
        'type'        => 'write',
        'ajax'        => true,
    ],
];

$services = [
    'Moodle Chatbot Service' => [
        'functions' => ['mod_moodlechatbot_get_enrolled_courses', 'mod_moodlechatbot_send_message'],
        'restrictedusers' => 0,
        'enabled' => 1,
    ],
];
