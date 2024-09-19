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
defined('MOODLE_INTERNAL') || die();

class mod_moodlechatbot_external extends external_api {

  // Parameters for get_bot_response
  public static function get_bot_response_parameters() {
    return new external_function_parameters(
      array(
        'message' => new external_value(PARAM_TEXT, 'The user message to the chatbot'),
      )
    );
  }

  // Returns for get_bot_response
  public static function get_bot_response_returns() {
    return new external_value(PARAM_TEXT, 'The chatbot response');
  }

  // Function to process the chatbot response
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
      $errorMessage = 'API Error: HTTP Code ' . $httpCode;
      if ($httpCode === 401) {
        $errorMessage .= ' - API authentication failed. Please check your Groq API key.';
      }
      debugging($errorMessage, DEBUG_DEVELOPER);
      throw new moodle_exception('apierror', 'mod_moodlechatbot', '', $httpCode, $errorMessage);
    }

    // Process the response
    return self::process_response($response);
  }

  // Process the API response and execute tool calls if necessary
  private static function process_response($response) {
    // Decode JSON response
    $data = json_decode($response, true);

    if (json_last_error() !== JSON_ERROR_NONE) {
      $errorMessage = 'JSON decode error: ' . json_last_error_msg();
      debugging($errorMessage, DEBUG_DEVELOPER);
      throw new moodle_exception('invalidjson', 'mod_moodlechatbot', '', null, $errorMessage);
    }

    // Check if the response includes tool calls
    if (isset($data['choices'][0]['message']['tool_calls'])) {
      $toolCalls = $data['choices'][0]['message']['tool_calls'];
      $toolResults = [];

      foreach ($toolCalls as $toolCall) {
        $functionName = $toolCall['function']['name'];
        $arguments = json_decode($toolCall['function']['arguments'], true);

        if (json_last_error() === JSON_ERROR_NONE) {
          $toolResults[] = self::execute_tool($functionName, $arguments);
        } else {
          $errorMessage = 'JSON decode error in tool arguments: ' . json_last_error_msg();
          debugging($errorMessage, DEBUG_DEVELOPER);
          throw new moodle_exception('invalidtoolarguments', 'mod_moodlechatbot', '', null, $errorMessage);
        }
      }

      // Return the processed tool results as plain text
      return implode("\n\n", $toolResults);
    }

    // If it's not a tool call, check for normal content
    if (isset($data['choices'][0]['message']['content'])) {
      return $data['choices'][0]['message']['content'];
    }

    // No valid response
    $errorMessage = 'No valid response from API: ' . print_r($data, true);
    debugging($errorMessage, DEBUG_DEVELOPER);
    throw new moodle_exception('noresponse', 'mod_moodlechatbot', '', null, $errorMessage);
  }

  // Execute the tool function based on the name
  private static function execute_tool($functionName, $args) {
    switch ($functionName) {
      case 'get_course_info':
        return self::get_course_info($args['course_id']);
      default:
        return "Unknown tool: $functionName";
    }
  }

  // Fetch course information from the database
  private static function get_course_info($courseId) {
    global $DB;

    // Fetch course information
    $course = $DB->get_record('course', array('id' => $courseId), '*', MUST_EXIST);

    // Return formatted course information as plain text
    $info = "Course ID: {$course->id}\n";
    $info .= "Course Name: {$course->fullname}\n";
    $info .= "Short Name: {$course->shortname}\n";
    $info .= "Course Summary: " . strip_tags($course->summary) . "\n";
    $info .= "Start Date: " . userdate($course->startdate) . "\n";
    $info .= "End Date: " . ($course->enddate ? userdate($course->enddate) : "No end date") . "\n";

    return $info; // Return plain text instead of JSON
  }
}
