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
 * This file keeps track of upgrades to the moodlechatbot module
 *
 * Sometimes, changes between versions involve alterations to database
 * structures and other major things that may break installations. The upgrade
 * function in this file will attempt to perform all the necessary actions to
 * upgrade your older installation to the current version. If there's something
 * it cannot do itself, it will tell you what you need to do.
 *
 * @package    mod_moodlechatbot
 * @copyright  2024 Your Name <your@email.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Execute moodlechatbot upgrade from the given old version
 *
 * @param int $oldversion
 * @return bool
 */
function xmldb_moodlechatbot_upgrade($oldversion) {
    global $DB;

    $dbman = $DB->get_manager(); // loads ddl manager and xmldb classes

    // Upgrade script starts here.

    if ($oldversion < 2024082901) {

        // Define field chatbotname to be added to moodlechatbot.
        $table = new xmldb_table('moodlechatbot');
        $field = new xmldb_field('chatbotname', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null, 'intro');

        // Conditionally launch add field chatbotname.
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }

        // Moodlechatbot savepoint reached.
        upgrade_mod_savepoint(true, 2024082901, 'moodlechatbot');
    }

    // Add more upgrade steps here as your plugin evolves.

    return true;
}
