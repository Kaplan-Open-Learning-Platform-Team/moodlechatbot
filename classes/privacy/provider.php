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

namespace mod_moodlechatbot\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\deletion_criteria;
use core_privacy\local\request\helper;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Privacy Subsystem implementation for mod_moodlechatbot.
 *
 * @package    mod_moodlechatbot
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
        // This plugin stores personal data.
        \core_privacy\local\metadata\provider,

        // This plugin is a core_user_data_provider.
        \core_privacy\local\request\plugin\provider {

    /**
     * Returns meta data about this system.
     *
     * @param collection $collection The initialised collection to add items to.
     * @return collection A listing of user data stored through this system.
     */
    public static function get_metadata(collection $collection) : collection {
        $collection->add_database_table(
            'moodlechatbot_messages',
            [
                'userid' => 'privacy:metadata:moodlechatbot_messages:userid',
                'message' => 'privacy:metadata:moodlechatbot_messages:message',
                'timecreated' => 'privacy:metadata:moodlechatbot_messages:timecreated',
            ],
            'privacy:metadata:moodlechatbot_messages'
        );

        return $collection;
    }

    /**
     * Get the list of contexts that contain user information for the specified user.
     *
     * @param int $userid The user to search.
     * @return contextlist The contextlist containing the list of contexts used in this plugin.
     */
    public static function get_contexts_for_userid(int $userid) : contextlist {
        $contextlist = new contextlist();

        $sql = "SELECT c.id
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {moodlechatbot} mcb ON mcb.id = cm.instance
            INNER JOIN {moodlechatbot_messages} mcbm ON mcbm.chatbotid = mcb.id
                 WHERE mcbm.userid = :userid";

        $params = [
            'modname'       => 'moodlechatbot',
            'contextlevel'  => CONTEXT_MODULE,
            'userid'        => $userid,
        ];

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * Export all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts to export information for.
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist)) {
            return;
        }

        $user = $contextlist->get_user();
        $userid = $user->id;

        list($contextsql, $contextparams) = $DB->get_in_or_equal($contextlist->get_contextids(), SQL_PARAMS_NAMED);

        $sql = "SELECT mcbm.id, mcbm.message, mcbm.timecreated, cm.id AS cmid
                  FROM {context} c
            INNER JOIN {course_modules} cm ON cm.id = c.instanceid AND c.contextlevel = :contextlevel
            INNER JOIN {modules} m ON m.id = cm.module AND m.name = :modname
            INNER JOIN {moodlechatbot} mcb ON mcb.id = cm.instance
            INNER JOIN {moodlechatbot_messages} mcbm ON mcbm.chatbotid = mcb.id
                 WHERE c.id {$contextsql}
                   AND mcbm.userid = :userid
              ORDER BY cm.id, mcbm.timecreated";

        $params = ['modname' => 'moodlechatbot', 'contextlevel' => CONTEXT_MODULE, 'userid' => $userid] + $contextparams;

        $messages = $DB->get_records_sql($sql, $params);

        foreach ($messages as $message) {
            $context = \context_module::instance($message->cmid);
            $contextdata = helper::get_context_data($context, $user);

            $contextdata = (object)array_merge((array)$contextdata, [
                'message' => $message->message,
                'timecreated' => \core_privacy\local\request\transform::datetime($message->timecreated),
            ]);

            writer::with_context($context)->export_data([], $contextdata);
        }
    }

    /**
     * Delete all data for all users in the specified context.
     *
     * @param \context $context The specific context to delete data for.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel != CONTEXT_MODULE) {
            return;
        }

        $cm = get_coursemodule_from_id('moodlechatbot', $context->instanceid);
        if (!$cm) {
            return;
        }

        $DB->delete_records('moodlechatbot_messages', ['chatbotid' => $cm->instance]);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            $cm = get_coursemodule_from_id('moodlechatbot', $context->instanceid);
            if (!$cm) {
                continue;
            }
            $DB->delete_records('moodlechatbot_messages', ['chatbotid' => $cm->instance, 'userid' => $userid]);
        }
    }
}
