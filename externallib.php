<?php
// This file is part of Moodle - http://moodle.org/
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
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

class mod_moodlechatbot_external extends external_api
{

  /**
   * Returns description of get_bot_response parameters
   * @return external_function_parameters
   */
  public static function get_bot_response_parameters()
  {
    return new external_function_parameters(
      array('message' => new external_value(PARAM_TEXT, 'The user message'))
    );
  }

  /**
   * Returns description of get_bot_response return values
   * @return external_single_structure
   */
  public static function get_bot_response_returns()
  {
    return new external_value(PARAM_TEXT, 'The bot response');
  }

  /**
   * Get bot response
   * @param string $message The user message
   * @return string The bot response
   */
  public static function get_bot_response($message)
  {
    global $CFG;

    // Parameter validation
    $params = self::validate_parameters(self::get_bot_response_parameters(), array('message' => $message));

    // Context validation
    $context = context_system::instance();
    self::validate_context($context);

    // Capability check
    require_capability('mod/moodlechatbot:use', $context);

    $apiKey = get_config('mod_moodlechatbot', 'apikey');
    $enableTools = get_config('mod_moodlechatbot', 'enabletools');

    if (empty($apiKey)) {
      throw new moodle_exception('apikeyerror', 'mod_moodlechatbot');
    }

    $apiUrl = 'https://api.groq.com/openai/v1/chat/completions';

    // Define tools
    $tools = [
      [
        'type' => 'function',
        'function' => [
          'name' => 'get_course_info',
          'description' => 'Get information about a specific course',
          'parameters' => [
            'type' => 'object',
            'properties' => [
              'course_id' => [
                'type' => 'integer',
                'description' => 'The ID of the course',
              ],
            ],
            'required' => ['course_id'],
          ],
        ],
      ],
      // Add more tools as needed
    ];

    $postFields = [
      'model' => 'llama3-groq-70b-8192-tool-use-preview',
      'messages' => [
        ['role' => 'system', 'content' => 'You are a helpful assistant in a Moodle learning environment.'],
        ['role' => 'user', 'content' => $params['message']]
      ],
      'max_tokens' => 150,
      'temperature' => 0.7
    ];

    if ($enableTools) {
      $postFields['tools'] = $tools;
      $postFields['tool_choice'] = 'auto';
    }

    $ch = curl_init($apiUrl);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json',
      'Authorization: Bearer ' . $apiKey
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postFields));

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Log raw API response for debugging
    debugging('Raw API response: ' . $response, DEBUG_DEVELOPER);

    curl_close($ch);

    // Check for HTTP errors
    if ($httpCode !== 200) {
      $errorMessage = 'API Error';
      if ($httpCode === 401) {
        $errorMessage = 'API authentication failed. Please check your Groq API key.';
      }
      debugging('API HTTP Code: ' . $httpCode . ' - ' . $errorMessage, DEBUG_DEVELOPER);
      throw new moodle_exception('apierror', 'mod_moodlechatbot', '', $httpCode, $errorMessage);
    }

    // Decode the JSON response
    $data = json_decode($response, true);

    // Improved error handling and debugging
    if (json_last_error() !== JSON_ERROR_NONE) {
      debugging('JSON decode error: ' . json_last_error_msg(), DEBUG_DEVELOPER);
      throw new moodle_exception('invalidjson', 'mod_moodlechatbot');
    }

    // Check if the 'choices' array and the 'message' content exist
    if (!isset($data['choices']) || !is_array($data['choices']) || empty($data['choices'])) {
      debugging('Unexpected API response structure: ' . print_r($data, true), DEBUG_DEVELOPER);
      throw new moodle_exception('invalidresponse', 'mod_moodlechatbot');
    }

    if (!isset($data['choices'][0]['message']['content'])) {
      debugging('Missing content in API response: ' . print_r($data['choices'][0], true), DEBUG_DEVELOPER);
      throw new moodle_exception('missingcontent', 'mod_moodlechatbot');
    }

    // Extract the bot response
    $botResponse = $data['choices'][0]['message']['content'];

    // Check if the bot wants to use a tool
    if ($enableTools && isset($data['choices'][0]['message']['tool_calls'])) {
      $toolCalls = $data['choices'][0]['message']['tool_calls'];
      foreach ($toolCalls as $toolCall) {
        $functionName = $toolCall['function']['name'];
        $functionArgs = json_decode($toolCall['function']['arguments'], true);

        // Execute the tool function
        $toolResult = self::execute_tool($functionName, $functionArgs);

        // Append the tool result to the conversation
        $botResponse .= "\n\nTool Result: " . $toolResult;
      }
    }

    return $botResponse;
  }

  /**
   * Execute a tool function
   * @param string $functionName The name of the function to execute
   * @param array $args The arguments for the function
   * @return string The result of the tool execution
   */
  private static function execute_tool($functionName, $args)
  {
    switch ($functionName) {
      case 'get_course_info':
        return self::get_course_info($args['course_id']);
        // Add cases for other tools as needed
      default:
        return "Unknown tool: $functionName";
    }
  }

  /**
   * Get course information
   * @param int $courseId The ID of the course
   * @return string Course information
   */
  private static function get_course_info($courseId)
  {
    global $DB;
    $course = $DB->get_record('course', array('id' => $courseId), '*', MUST_EXIST);
    return "Course Name: {$course->fullname}, Short Name: {$course->shortname}, Start Date: " . userdate($course->startdate);
  }
}
