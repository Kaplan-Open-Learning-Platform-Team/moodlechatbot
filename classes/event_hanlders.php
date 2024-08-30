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

namespace mod_moodlechatbot;

defined('MOODLE_INTERNAL') || die();

/**
 * Event handler for mod_moodlechatbot.
 *
 * @package    mod_moodlechatbot
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class event_handlers {

    /**
     * Handler for message_sent event.
     *
     * @param \mod_moodlechatbot\event\message_sent $event The event.
     */
    public static function message_sent_handler(\mod_moodlechatbot\event\message_sent $event) {
        global $DB;

        // Log the message.
        $logdata = [
            'chatbotid' => $event->other['chatbotid'],
            'userid' => $event->userid,
            'messageid' => $event->objectid,
            'timecreated' => $event->timecreated
        ];
        $DB->insert_record('moodlechatbot_log', $logdata);

        // You could add more actions here, such as:
        // - Sending a notification
        // - Updating statistics
        // - Triggering the AI response
    }

    /**
     * Handler for bot_responded event.
     *
     * @param \mod_moodlechatbot\event\bot_responded $event The event.
     */
    public static function bot_responded_handler(\mod_moodlechatbot\event\bot_responded $event) {
        global $DB;

        // Log the bot response.
        $logdata = [
            'chatbotid' => $event->other['chatbotid'],
            'messageid' => $event->objectid,
            'timecreated' => $event->timecreated
        ];
        $DB->insert_record('moodlechatbot_log', $logdata);

        // You could add more actions here, such as:
        // - Updating conversation statistics
        // - Triggering follow-up actions based on the bot's response
    }

    /**
     * Handler for course_module_created event.
     *
     * @param \core\event\course_module_created $event The event.
     */
    public static function course_module_created_handler(\core\event\course_module_created $event) {
        global $DB;

        // Check if the created module is a moodlechatbot.
        if ($event->other['modulename'] === 'moodlechatbot') {
            // Perform any necessary setup for the new chatbot instance.
            $chatbotid = $event->other['instanceid'];
            $coursemoduleid = $event->contextinstanceid;

            // Example: Initialize the chatbot with a welcome message.
            $welcomemessage = [
                'chatbotid' => $chatbotid,
                'message' => 'Welcome to the new chatbot! How can I assist you?',
                'timecreated' => time(),
                'isbot' => 1
            ];
            $DB->insert_record('moodlechatbot_messages', $welcomemessage);

            // You could add more initialization steps here.
        }
    }
}
