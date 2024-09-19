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
      $errorMessage = 'API Error: HTTP Code ' . $httpCode;
      if ($httpCode === 401) {
        $errorMessage .= ' - API authentication failed. Please check your Groq API key.';
      }
      debugging($errorMessage, DEBUG_DEVELOPER);
      throw new moodle_exception('apierror', 'mod_moodlechatbot', '', $httpCode, $errorMessage);
    }

    // Process the response
    $botResponse = self::process_response($response);

    // Ensure the response is a plain text string
    return strip_tags($botResponse);
  }

  /**
   * Process the API response
   * @param string $response The raw API response
   * @return string The processed response
   */
  private static function process_response($response)
  {
    // Check if the response is a tool call
    if (strpos($response, '<tool_call>') !== false) {
      // Extract the JSON content from the tool call
      $jsonContent = strip_tags($response);
      $data = json_decode($jsonContent, true);

      if (json_last_error() !== JSON_ERROR_NONE) {
        $errorMessage = 'JSON decode error in tool call: ' . json_last_error_msg();
        debugging($errorMessage, DEBUG_DEVELOPER);
        throw new moodle_exception('invalidjson', 'mod_moodlechatbot', '', null, $errorMessage);
      }

      // Execute the tool function
      return self::execute_tool($data['name'], $data['arguments']);
    }

    // If it's not a tool call, proceed with normal JSON parsing
    $data = json_decode($response, true);

    // Log the decoded response for better understanding
    debugging('Decoded API response: ' . print_r($data, true), DEBUG_DEVELOPER);

    // Improved error handling and debugging
    if (json_last_error() !== JSON_ERROR_NONE) {
      $errorMessage = 'JSON decode error: ' . json_last_error_msg();
      debugging($errorMessage, DEBUG_DEVELOPER);
      throw new moodle_exception('invalidjson', 'mod_moodlechatbot', '', null, $errorMessage);
    }

    // Check if the 'choices' array exists
    if (!isset($data['choices']) || !is_array($data['choices']) || empty($data['choices'])) {
      $errorMessage = 'Unexpected API response structure: ' . print_r($data, true);
      debugging($errorMessage, DEBUG_DEVELOPER);
      throw new moodle_exception('invalidresponse', 'mod_moodlechatbot', '', null, $errorMessage);
    }

    $choice = $data['choices'][0];

    // Check if there's a content field
    if (isset($choice['message']['content'])) {
      return $choice['message']['content'];
    }

    // Handle tool calls
    if (isset($choice['message']['tool_calls'])) {
      $toolResults = [];
      foreach ($choice['message']['tool_calls'] as $toolCall) {
        $functionName = $toolCall['function']['name'];
        $functionArgs = json_decode($toolCall['function']['arguments'], true);

        // Execute the tool function
        $toolResults[] = self::execute_tool($functionName, $functionArgs);
      }
      return implode("\n\n", $toolResults);
    }

    // If we still don't have a response, throw an error
    $errorMessage = 'No content or valid tool calls in API response: ' . print_r($choice, true);
    debugging($errorMessage, DEBUG_DEVELOPER);
    throw new moodle_exception('nocontentortoolcalls', 'mod_moodlechatbot', '', null, $errorMessage);
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

    // Fetch course information from the database
    $course = $DB->get_record('course', array('id' => $courseId), '*', MUST_EXIST);

    // Prepare the course information string
    $info = "Course ID: {$course->id}\n";
    $info .= "Course Name: {$course->fullname}\n";
    $info .= "Short Name: {$course->shortname}\n";
    $info .= "Course Summary: " . strip_tags($course->summary) . "\n";
    $info .= "Start Date: " . userdate($course->startdate) . "\n";
    $info .= "End Date: " . ($course->enddate ? userdate($course->enddate) : "No end date") . "\n";

    return $info;
  }
}
