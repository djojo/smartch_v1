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
 * Get course stats service
 *
 * @package   theme_remui
 * @copyright (c) 2023 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_remui\external;

defined('MOODLE_INTERNAL') || die;

use external_function_parameters;
use external_value;
use core_course_list_element;
use moodle_url;
use stdClass;

require_once(__DIR__ . '/../../../../calendar/externallib.php');
// require_once('/../../views/utils.php');
// require_once($CFG->dirroot . '/course/lib.php');
// require_once('./smartch_functions.php');

/**
 * Get course stats trait
 * @copyright (c) 2022 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait get_smartch_calendar
{
    /**
     * Describes the parameters for get_smartch_my_courses
     * @return external_function_parameters
     */
    public static function get_smartch_calendar_parameters()
    {
        return new external_function_parameters(
            array(
                'timestart' => new external_value(PARAM_INT, 'timestart'),
                'timeend' => new external_value(PARAM_INT, 'timeend')
            )
        );
    }


    /**
     * Save order of sections in array of configuration format
     * @param  int $courseid Course id
     * @return boolean       true
     */
    public static function get_smartch_calendar($timestart = null, $timeend = null)
    {
        global $DB, $CFG, $USER;

        // Validation for context is needed.
        $context = \context_system::instance();
        self::validate_context($context);

        //On va chercher le rôle le plus haut de l'utilisateur
        $rolename = "";
        $assignments = $DB->get_records('role_assignments', ['userid' => $USER->id]);
        foreach ($assignments as $assignment) {
            $role = $DB->get_record('role', ['id' => $assignment->roleid]);
            //on renvoi le rôle le plus haut
            if ($role->shortname == "super-admin") {
                $rolename = "super-admin";
            } else if ($role->shortname == "manager") {
                if ($rolename != "super-admin") {
                    $rolename = "manager";
                }
            } else if ($role->shortname == "smalleditingteacher") {
                if ($rolename != "super-admin" && $rolename != "manager") {
                    $rolename = "smalleditingteacher";
                }
            } else if ($role->shortname == "editingteacher") {
                if ($rolename != "super-admin" && $rolename != "manager" && $rolename != "smalleditingteacher") {
                    $rolename = "editingteacher";
                }
            } else if ($role->shortname == "teacher") {
                if ($rolename != "super-admin" && $rolename != "manager" && $rolename != "smalleditingteacher" && $rolename != "editingteacher") {
                    $rolename = "teacher";
                }
            } else if ($role->shortname == "noneditingteacher") {
                if ($rolename != "super-admin" && $rolename != "manager" && $rolename != "teacher" && $rolename != "smalleditingteacher" && $rolename != "editingteacher") {
                    $rolename = "noneditingteacher";
                }
            } else if ($role->shortname == "student") {
                if ($rolename != "super-admin" && $rolename != "manager" && $rolename != "teacher" && $rolename != "noneditingteacher" && $rolename != "smalleditingteacher" && $rolename != "editingteacher") {
                    $rolename = "student";
                }
            }
        }

        $events = array();

        $filter = '';

        if ($timestart) {
            // $timeend = $timestart + 60 * 60 * 24;
            // $timestart = $timestart - 60 * 60 * 24;
            $filter = ' AND sp.startdate > ' . $timestart . ' ';
            // $filter = ' AND sp.timestart > ' . $timestart . ' AND sp.timestart < ' . $timeend;
        }
        if ($timeend) {
            $filter .= ' AND sp.startdate < ' . $timeend . ' ';
        }

        //On va chercher les plannings
        global $DB;
        $plannings = $DB->get_records_sql('SELECT sp.id, sp.sectionid, sp.startdate, sp.enddate, sp.geforplanningid, c.id AS courseid, c.fullname, g.name as groupname, g.id as groupid, ss.adress1, ss.adress2, ss.zip, ss.city, ss.location
        FROM mdl_smartch_planning sp
        JOIN mdl_smartch_session ss ON ss.id = sp.sessionid
        JOIN mdl_groups g ON g.id = ss.groupid
        JOIN mdl_course c ON c.id = g.courseid
        JOIN mdl_groups_members gm ON gm.groupid = g.id
        WHERE gm.userid = ' . $USER->id . '
        AND c.visible = 1
        ' . $filter . '
        ORDER BY sp.startdate', null);

        foreach ($plannings as $planning) {

            $event = new stdClass();

            //on change l'url en fonction du rôle
            if ($rolename == "smalleditingteacher") {
                $event->url = '/theme/remui/views/adminteam.php?teamid=' . $planning->groupid . '&sectionid=' . $planning->sectionid . '#modulesformation';
            } else {
                $event->url = '/theme/remui/views/formation.php?id=' . $planning->courseid . '&sectionid=' . $planning->sectionid . '#modulesformation';
            }

            $event->coursename = $planning->fullname;
            $event->title = 'Session du ' . userdate($planning->startdate, '%d/%m à %H:%M');
            // $event->title = $planning->fullname;
            // $event->title = $planning->fullname . " - " . $planning->geforplanningid;
            $event->groupname = $planning->groupname;
            $event->adress1 = $planning->adress1;
            $event->adress2 = $planning->adress2;
            $event->zip = $planning->zip;
            $event->city = $planning->city;
            $event->info = $planning->location;
            $event->actual = $rolename;

            //la matiere
            $matiereobject = $DB->get_record('course_sections', ['id' => $planning->sectionid]);
            if ($matiereobject) {
                $matiere = $matiereobject->name;
            } else {
                $matiere = "Généralités";
            }
            $event->matiere = $matiere;
            // $event->start = userdate($planning->startdate, '%Y-%m-%dT%H:%M:%S');
            $event->start = date('Y-m-d\TH:i:s', $planning->startdate);
            // $event->end = userdate($planning->enddate, '%Y-%m-%dT%H:%M:%S');
            $event->end = date('Y-m-d\TH:i:s', $planning->enddate);
            array_push($events, $event);
        }

        //On formate

        // $events = " nooo";
        // array_push($mycourses, $el);

        // $data['rolename'] = $rolename;
        // $data['mycourses'] = $mycourses;


        // $out = array_values($courses);
        return json_encode($events);
    }

    /**
     * Describes the get_smartch_my_courses return value
     * @return external_value
     */
    public static function get_smartch_calendar_returns()
    {
        return new external_value(PARAM_RAW, 'Courses of a user in JSON Format');
    }
}
