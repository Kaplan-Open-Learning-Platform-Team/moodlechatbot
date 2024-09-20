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

    self::$groq = new Groq(['api_key' => $apiKey]);
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
    global $CFG;

    // Parameter validation
    $params = self::validate_parameters(self::get_bot_response_parameters(), array('message' => $message));

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
      // Add more tools as needed
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

        if (isset($choice['message']['tool_calls'])) {
          $toolCalls = $choice['message']['tool_calls'];
          $toolResults = [];
          foreach ($toolCalls as $toolCall) {
            $functionName = $toolCall['function']['name'];
            $functionArgs = json_decode($toolCall['function']['arguments'], true);
            $toolResults[] = self::execute_tool($functionName, $functionArgs);
          }
          $nextMessageContent = implode("\n\n", $toolResults);
          $messages[] = [
            'role' => 'user',
            'content' => "Tool Result: \n" . $nextMessageContent
          ];
        } else if (isset($choice['message']['content'])) {
          $botResponse = $choice['message']['content'];
          break;
        } else {
          throw new moodle_exception('nocontentortoolcalls', 'mod_moodlechatbot');
        }
      }
    } catch (GroqException $e) {
      debugging('Groq API Error: ' . $e->getMessage(), DEBUG_DEVELOPER);
      throw new moodle_exception('apierror', 'mod_moodlechatbot', '', $e->getCode(), $e->getMessage());
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
