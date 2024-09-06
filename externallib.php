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
 * Moodle Chat Bot external lib functions
 *
 * @package    mod_moodlechatbot
 * @category   external
 * @copyright  2024 Kaplan Open Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die;

require_once("$CFG->libdir/externallib.php");

class mod_moodlechatbot_external extends external_api {

    /**
     * Returns description of get_bot_response parameters
     * @return external_function_parameters
     */
    public static function get_bot_response_parameters() {
        return new external_function_parameters(
            array('message' => new external_value(PARAM_TEXT, 'The user message'))
        );
    }

    /**
     * Returns description of get_bot_response return values
     * @return external_single_structure
     */
    public static function get_bot_response_returns() {
        return new external_value(PARAM_TEXT, 'The bot response');
    }

    /**
     * Get bot response
     * @param string $message The user message
     * @return string The bot response
     */
    public static function get_bot_response($message) {
        global $CFG;

        // Parameter validation
        $params = self::validate_parameters(self::get_bot_response_parameters(), array('message' => $message));

        // Context validation
        $context = context_system::instance();
        self::validate_context($context);

        // Capability check
        require_capability('mod/moodlechatbot:use', $context);

        $apiKey = get_config('mod_moodlechatbot', 'apikey');
        $apiSecret = get_config('mod_moodlechatbot', 'apisecret');

        if (empty($apiKey) || empty($apiSecret)) {
            throw new moodle_exception('apicredentialsmissing', 'mod_moodlechatbot');
        }

        $apiUrl = 'https://api.groq.com/openai/v1/chat/completions';

        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $apiKey
        ]);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
            'model' => 'mixtral-8x7b-32768',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a helpful assistant in a Moodle learning environment.'],
                ['role' => 'user', 'content' => $params['message']]
            ],
            'max_tokens' => 150,
            'temperature' => 0.7
        ]));

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $errorMessage = 'API Error';
            if ($httpCode === 401) {
                $errorMessage = 'API authentication failed. Please check your API credentials.';
            }
            throw new moodle_exception('apierror', 'mod_moodlechatbot', '', $httpCode, $errorMessage);
        }

        $data = json_decode($response, true);
        if (!isset($data['choices'][0]['message']['content'])) {
            throw new moodle_exception('invalidresponse', 'mod_moodlechatbot');
        }

        return $data['choices'][0]['message']['content'];
    }
}
