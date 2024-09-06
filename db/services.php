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
 * @category   external
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'mod_moodlechatbot_send_message' => array(
        'classname'   => 'mod_moodlechatbot_external',
        'methodname'  => 'send_message',
        'classpath'   => 'mod/moodlechatbot/externallib.php',
        'description' => 'Send a message to the chatbot and get a response',
        'type'        => 'write',
        'ajax'        => true,
        'capabilities'=> 'mod/moodlechatbot:interact',
    ),
);

$services = array(
    'Moodle Chatbot Service' => array(
        'functions' => array('mod_moodlechatbot_send_message'),
        'restrictedusers' => 0,
        'enabled' => 1,
    ),
);
