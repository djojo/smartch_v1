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
 * Edwiser RemUI
 *
 * @package   theme_remui
 * @copyright (c) 2023 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace theme_remui\controller;

// use \stdClass;
// use \context;


/**
 * Class EventsController will handle the events triggered by Moodle.
 */
class EventsController {

    public static function user_created_event($eventdata) {

        global $DB,$CFG;
        require_once($CFG->dirroot . '/cohort/lib.php');

        $data = $eventdata->get_data();

        $userid = $data['relateduserid'];

        //on va chercher la cohorte
        $maincohort = $DB->get_record_sql('SELECT * 
        FROM mdl_cohort co
        WHERE co.name = "Employés FFF"', null);

        if($maincohort){
            //On ajoute à la cohorte
            cohort_add_member($maincohort->id, $userid);
        }
    }
    public static function user_enrollment_event($eventdata) {

        $data = $eventdata->get_data();

        $userid = $data['relateduserid'];

        set_user_preference('course_cache_reset', true, $userid);

        // Update Enrollment History Data.
        $pnotification = new \theme_remui\productnotifications();
        $pnotification->update_enrollment_history();
    }

    public static function course_updation_event($eventdata) {
        // Set Global Config to acknowledge to reset the cache.
        // Can reset order is not just for enrolled students.
        // Need to reset the cache of all users as that course get displayed in All Courses Tab.
        set_config('cache_reset_time', time(), 'theme_remui');
    }
    public static function user_loggedin_event($eventdata) {
        global $USER;
        set_user_preference('enable_focus_mode', false, $USER->id);
    }
}
