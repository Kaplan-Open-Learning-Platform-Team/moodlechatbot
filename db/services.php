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
 * Moodle Chat Bot external functions and service definitions.
 *
 * @package    mod_moodlechatbot
 * @category   webservice
 * @copyright  2024 Kaplan Open Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = array(
    'mod_moodlechatbot_get_bot_response' => array(
        'classname'   => 'mod_moodlechatbot_external',
        'methodname'  => 'get_bot_response',
        'classpath'   => 'mod/moodlechatbot/externallib.php',
        'description' => 'Get a response from the chat bot',
        'type'        => 'read',
        'ajax'        => true,
    ),
);

$services = array(
    'Moodle Chat Bot Service' => array(
        'functions' => array('mod_moodlechatbot_get_bot_response'),
        'restrictedusers' => 0,
        'enabled' => 1,
    )
);
