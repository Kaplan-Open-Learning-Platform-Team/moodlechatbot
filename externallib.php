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

require_once($CFG->libdir . "/externallib.php");

// Include Composer autoloader
$composerAutoload = $CFG->dirroot . '/mod/moodlechatbot/vendor/autoload.php';
if (file_exists($composerAutoload)) {
  require_once($composerAutoload);
} else {
  throw new moodle_exception('composerautloaderror', 'mod_moodlechatbot');
}

use LucianoTonet\GroqPHP\Groq;
use LucianoTonet\GroqPHP\GroqException;

class mod_moodlechatbot_external extends external_api
{
  private static $groq;

  /**
   * Initialize the Groq client
   */
  private static function init_groq()
  {
    $apiKey = get_config('mod_moodlechatbot', 'apikey');
    if (empty($apiKey)) {
      throw new moodle_exception('apikeyerror', 'mod_moodlechatbot');
    }

    self::$groq = new Groq($apiKey);
  }

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
    global $USER;

    // Parameter validation
    $params = self::validate_parameters(self::get_bot_response_parameters(), ['message' => $message]);

    // Context validation
    $context = context_system::instance();
    self::validate_context($context);

    // Capability check
    require_capability('mod/moodlechatbot:use', $context);

    self::init_groq();

    $enableTools = get_config('mod_moodlechatbot', 'enabletools');

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
      [
        'type' => 'function',
        'function' => [
          'name' => 'get_user_enrolled_courses',
          'description' => 'Get a list of courses a user is enrolled in',
          'parameters' => [], // No parameters needed
        ],
      ],
    ];

    $messages = [
      ['role' => 'system', 'content' => 'You are a helpful assistant in a Moodle learning environment.'],
      ['role' => 'user', 'content' => $params['message']]
    ];

    $botResponse = '';

    try {
      while (true) {
        $completionParams = [
          'model' => 'llama3-groq-70b-8192-tool-use-preview',
          'messages' => $messages,
          'max_tokens' => 2000,
          'temperature' => 0.7
        ];

        if ($enableTools) {
          $completionParams['tools'] = $tools;
          $completionParams['tool_choice'] = 'auto';
        }

        $response = self::$groq->chat()->completions()->create($completionParams);
        $choice = $response['choices'][0];

        // Check if there are tool calls in the response
        if (isset($choice['message']['tool_calls'])) {
          $toolCalls = $choice['message']['tool_calls'];
          $toolResults = [];

          // Loop through each tool call and execute the corresponding function
          foreach ($toolCalls as $toolCall) {
            $functionName = $toolCall['function']['name'];
            $functionArgs = json_decode($toolCall['function']['arguments'], true);

            // Automatically pass the current user ID for enrolled courses
            if ($functionName === 'get_user_enrolled_courses') {
              $functionArgs = ['user_id' => $USER->id]; // Use the current user's ID
            }

            // Execute the tool function with the arguments
            $toolResults[] = self::execute_tool($functionName, $functionArgs);
          }

          // Append the results of the tool execution to the bot's response
          $botResponse .= implode("\n\n", $toolResults);

          // Update the conversation with the tool results
          $messages[] = [
            'role' => 'user',
            'content' => "Tool Result: \n" . implode("\n\n", $toolResults)
          ];
        } else if (isset($choice['message']['content'])) {
          // If there's no tool call, return the content from the chatbot
          $botResponse .= $choice['message']['content'];
          break;
        } else {
          throw new moodle_exception('nocontentortoolcalls', 'mod_moodlechatbot');
        }
      }
    } catch (GroqException $e) {
      debugging('Groq API Error: ' . $e->getMessage(), DEBUG_DEVELOPER);
      throw new moodle_exception('apierror', 'mod_moodlechatbot', '', $e->getCode(), $e->getMessage());
    }

    // Ensure the return is plain text
    return clean_param($botResponse, PARAM_TEXT);
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
      case 'get_user_enrolled_courses':
        return self::get_user_enrolled_courses($args['user_id']);
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

    return trim($info);
  }

  /**
   * Get a list of courses a user is enrolled in
   * @param int $userId The ID of the user
   * @return string List of enrolled courses
   */
  private static function get_user_enrolled_courses($userId)
  {
    global $DB;

    // Fetch user courses
    $courses = enrol_get_users_courses($userId);

    if (empty($courses)) {
      return "No courses found for user ID: $userId.";
    }

    // Prepare the course information string
    $courseList = "Courses for User ID $userId:\n";
    foreach ($courses as $course) {
      $courseList .= "- {$course->fullname} (ID: {$course->id})\n";
    }

    return trim($courseList);
  }
}

