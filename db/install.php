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
 * Theme remui upgrade hook
 * @package   theme_remui
 * @copyright (c) 2023 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Yogesh Shirsath
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/theme/remui/db/upgrade.php');
require_once($CFG->dirroot . '/theme/remui/lib.php');

/**
 * upgrade this edwiserform plugin database
 * @param int $oldversion The old version of the edwiserform local plugin
 * @return bool
 */
function xmldb_theme_remui_install()
{
    global $CFG, $DB;
    
    if (!PHPUNIT_TEST && !defined('BEHAT_UTIL')) {
        //theme_remui_course_custom_fields();
    }

    $dbman = $DB->get_manager();
    // if ($oldversion < 2024042502) {
        // Suppose que 2024042500 est la nouvelle version incluant la table smartch_config.

        // Define the table smartch_config to be created.
        $table = new xmldb_table('smartch_config');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('key', XMLDB_TYPE_CHAR, '255', null, XMLDB_NOTNULL, null, null);
        $table->add_field('value', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table smartch_config.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('key', XMLDB_KEY_UNIQUE, ['key']);

        // Conditionally launch create table for smartch_config.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

    // }

    // if ($oldversion < 2024042502) {
        // Suppose que 2024042500 est la nouvelle version incluant la table smartch_slider.

        // Define the table smartch_slider to be created.
        $table = new xmldb_table('smartch_slider');
        $table->add_field('id', XMLDB_TYPE_INTEGER, '10', null, XMLDB_NOTNULL, XMLDB_SEQUENCE, null);
        $table->add_field('imagefixe', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('image1', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('image2', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('image3', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('image4', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('image5', XMLDB_TYPE_TEXT, null, null, null, null, null);
        $table->add_field('sliderarray', XMLDB_TYPE_TEXT, null, null, null, null, null);

        // Adding keys to table smartch_slider.
        $table->add_key('primary', XMLDB_KEY_PRIMARY, ['id']);
        $table->add_key('key', XMLDB_KEY_UNIQUE, ['key']);

        // Conditionally launch create table for smartch_slider.
        if (!$dbman->table_exists($table)) {
            $dbman->create_table($table);
        }

    // }


    // Init product notification configuration.
    $pnotification = new \theme_remui\productnotifications();
    $pnotification->init_history_config();

    import_user_tour();
    return true;
}
