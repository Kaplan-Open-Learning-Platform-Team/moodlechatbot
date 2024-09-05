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
 * Renderer for mod_moodlechatbot.
 *
 * @package    mod_moodlechatbot
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer class for mod_moodlechatbot.
 *
 * @package    mod_moodlechatbot
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_moodlechatbot_renderer extends plugin_renderer_base {

    /**
     * Render the main chat interface.
     *
     * @param int $chatbotid The ID of the chat bot instance.
     * @param array $messages An array of message objects to display.
     * @return string HTML for the chat interface.
     */
    public function render_chat_interface($chatbotid, $messages = []) {
        $data = [
            'chatbotid' => $chatbotid,
            'messages' => $messages,
            'inputplaceholder' => get_string('typemessage', 'mod_moodlechatbot'),
            'sendlabel' => get_string('send', 'mod_moodlechatbot')
        ];
        return $this->render_from_template('mod_moodlechatbot/chat_interface', $data);
    }

    /**
     * Render a single chat message.
     *
     * @param object $message The message object.
     * @return string HTML for the message.
     */
    public function render_message($message) {
        $data = [
            'sender' => $message->sender,
            'content' => $message->content,
            'timestamp' => userdate($message->timestamp),
            'isbot' => !empty($message->isbot)
        ];
        return $this->render_from_template('mod_moodlechatbot/message', $data);
    }

    /**
     * Render the input area for the chat.
     *
     * @param int $chatbotid The ID of the chat bot instance.
     * @return string HTML for the input area.
     */
    public function render_input_area($chatbotid) {
        $data = [
            'chatbotid' => $chatbotid,
            'inputplaceholder' => get_string('typemessage', 'mod_moodlechatbot'),
            'sendlabel' => get_string('send', 'mod_moodlechatbot')
        ];
        return $this->render_from_template('mod_moodlechatbot/input_area', $data);
    }

    /**
     * Render a loading indicator.
     *
     * @return string HTML for the loading indicator.
     */
    public function render_loading_indicator() {
        return $this->render_from_template('mod_moodlechatbot/loading', []);
    }

    /**
     * Render an error message.
     *
     * @param string $message The error message to display.
     * @return string HTML for the error message.
     */
    public function render_error_message($message) {
        return $this->render_from_template('mod_moodlechatbot/error', ['message' => $message]);
    }
}
