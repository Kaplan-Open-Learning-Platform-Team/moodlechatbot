<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Plugin upgrade steps are defined here.
 *
 * @package     mod_moodlechatbot
 * @category    upgrade
 * @copyright   2024 Your Name <your@email.com>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute mod_moodlechatbot upgrade from the given old version.
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_moodlechatbot_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager();

    // For future upgrades, start the if statements from here.

    // Upgrade step 1: Add 'botname' field to moodlechatbot table.
    if ($oldversion < 2024010101) {
        // Define field botname to be added to moodlechatbot.
        $table = new xmldb_table('moodlechatbot');
        $field = new xmldb_field('botname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, 'Moodle Bot', 'introformat');

        // Conditionally launch add field botname.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Moodlechatbot savepoint reached.
        upgrade_mod_savepoint(true, 2024010101, 'moodlechatbot');
    }

    // Upgrade step 2: Add 'welcomemessage' field to moodlechatbot table.
    if ($oldversion < 2024010102) {
        // Define field welcomemessage to be added to moodlechatbot.
        $table = new xmldb_table('moodlechatbot');
        $field = new xmldb_field('welcomemessage', XMLDB_TYPE_TEXT, null, null, null, null, null, 'botname');

        // Conditionally launch add field welcomemessage.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Moodlechatbot savepoint reached.
        upgrade_mod_savepoint(true, 2024010102, 'moodlechatbot');
    }

    // Upgrade step 3: Add 'maxmessages' field to moodlechatbot table.
    if ($oldversion < 2024010103) {
        // Define field maxmessages to be added to moodlechatbot.
        $table = new xmldb_table('moodlechatbot');
        $field = new xmldb_field('maxmessages', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, null, '100', 'welcomemessage');

        // Conditionally launch add field maxmessages.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Moodlechatbot savepoint reached.
        upgrade_mod_savepoint(true, 2024010103, 'moodlechatbot');
    }

    return true;
}
