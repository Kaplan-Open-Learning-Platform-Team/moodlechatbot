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

namespace mod_moodlechatbot\output;

defined('MOODLE_INTERNAL') || die();

use plugin_renderer_base;
use renderable;

/**
 * Renderer for mod_moodlechatbot.
 *
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends plugin_renderer_base {

    /**
     * Render the chat interface.
     *
     * @param renderable $chat The chat renderable object.
     * @return string HTML to output.
     */
    public function render_chat(renderable $chat) {
        $data = $chat->export_for_template($this);
        return $this->render_from_template('mod_moodlechatbot/chat_interface', $data);
    }

    /**
     * Render a single chat message.
     *
     * @param \stdClass $message The message object.
     * @return string HTML to output.
     */
    public function render_message($message) {
        $data = [
            'id' => $message->id,
            'userid' => $message->userid,
            'message' => format_text($message->message, FORMAT_MOODLE),
            'timecreated' => userdate($message->timecreated),
            'isbot' => !empty($message->isbot),
        ];
        return $this->render_from_template('mod_moodlechatbot/chat_message', $data);
    }

    /**
     * Render the chat input form.
     *
     * @param int $chatbotid The ID of the chatbot instance.
     * @return string HTML to output.
     */
    public function render_input_form($chatbotid) {
        $data = [
            'chatbotid' => $chatbotid,
            'sesskey' => sesskey(),
        ];
        return $this->render_from_template('mod_moodlechatbot/chat_input', $data);
    }
}
