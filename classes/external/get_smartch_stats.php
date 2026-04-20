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

require_once($CFG->libdir . '/completionlib.php');

/**
 * Get course stats trait
 * @copyright (c) 2022 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait get_smartch_stats
{
    /**
     * Describes the parameters for get_smartch_stats
     * @return external_function_parameters
     */
    public static function get_smartch_stats_parameters()
    {
        return new external_function_parameters(
            array()
        );
    }

    /**
     * Save order of sections in array of configuration format
     * @param  int $courseid Course id
     * @return boolean       true
     */
    public static function get_smartch_stats()
    {
        global $PAGE, $USER, $DB;
        // Validation for context is needed.
        $context = \context_system::instance();
        self::validate_context($context);
        $coursepercentage = new \core_completion\progress();

        $coursescompleted = 0;
        $activitiescomplete = 0;
        $activitiesdue = 0;
        $totalactivities = 0;

        $querycourses = 'SELECT DISTINCT c.id, c.fullname FROM mdl_course c
            JOIN mdl_user_enrolments ue ON ue.userid = ' . $USER->id . '
            JOIN mdl_enrol e ON e.id = ue.enrolid AND e.courseid = c.id
            WHERE c.format != "site" AND c.visible = 1 AND ue.status = 0';
        $courses = $DB->get_records_sql($querycourses, null);

        foreach ($courses as $key => $course) {
            // e-learning : même logique que getCompletionPourcent (completion > 0, exclut face2face/folder/smartchfolder)
            $totalactivities += (int) $DB->count_records_sql(
                'SELECT COUNT(cm.id) FROM mdl_course_modules cm
                 JOIN mdl_modules m ON m.id = cm.module
                 WHERE cm.course = ? AND cm.completion > 0 AND cm.deletioninprogress = 0
                 AND m.name NOT IN (\'face2face\', \'folder\', \'smartchfolder\')',
                [$course->id]
            );
            $activitiescomplete += (int) $DB->count_records_sql(
                'SELECT COUNT(DISTINCT cmc.coursemoduleid) FROM mdl_course_modules_completion cmc
                 JOIN mdl_course_modules cm ON cm.id = cmc.coursemoduleid
                 JOIN mdl_modules m ON m.id = cm.module
                 WHERE cmc.userid = ? AND cm.course = ? AND cm.completion > 0 AND cm.deletioninprogress = 0
                 AND m.name NOT IN (\'face2face\', \'folder\', \'smartchfolder\') AND cmc.completionstate >= 1',
                [$USER->id, $course->id]
            );

            // séances présentielles : MIN(plannings, face2face complétés) par section
            $group = $DB->get_record_sql(
                'SELECT g.id FROM mdl_groups g
                 JOIN mdl_groups_members gm ON gm.groupid = g.id
                 WHERE gm.userid = ? AND g.courseid = ?',
                [$USER->id, $course->id]
            );
            if ($group) {
                $session = $DB->get_record('smartch_session', ['groupid' => $group->id]);
                if ($session) {
                    $sectionStats = $DB->get_records_sql(
                        'SELECT sp.sectionid,
                                COUNT(DISTINCT sp.id) as nb_plannings,
                                COUNT(DISTINCT cm.id) as nb_face2face
                         FROM mdl_smartch_planning sp
                         JOIN mdl_course_modules cm ON cm.section = sp.sectionid AND cm.course = ?
                         JOIN mdl_modules m ON m.id = cm.module AND m.name = \'face2face\'
                         WHERE sp.sessionid = ? AND cm.completion > 0
                         GROUP BY sp.sectionid',
                        [$course->id, $session->id]
                    );
                    foreach ($sectionStats as $s) {
                        $totalactivities += min($s->nb_plannings, $s->nb_face2face);
                    }

                    $sectionDone = $DB->get_records_sql(
                        'SELECT sp.sectionid,
                                COUNT(DISTINCT sp.id) as nb_plannings,
                                COUNT(DISTINCT cm.id) as nb_face2face
                         FROM mdl_smartch_planning sp
                         JOIN mdl_course_modules cm ON cm.section = sp.sectionid AND cm.course = ?
                         JOIN mdl_modules m ON m.id = cm.module AND m.name = \'face2face\'
                         JOIN mdl_course_modules_completion cmc ON cmc.coursemoduleid = cm.id
                              AND cmc.userid = ? AND cmc.completionstate >= 1
                         WHERE sp.sessionid = ? AND cm.completion > 0
                         GROUP BY sp.sectionid',
                        [$course->id, $USER->id, $session->id]
                    );
                    foreach ($sectionDone as $s) {
                        $activitiescomplete += min($s->nb_plannings, $s->nb_face2face);
                    }
                }
            }
        }

        //on calcule
        if ($totalactivities == 0) {
            $activitiesprogress = 0;
        } else {
            $activitiesprogress = number_format($activitiescomplete / $totalactivities * 100, 2);
        }

        $stats['coursesenrolled'] = count($courses);
        $stats['coursescompleted'] = $coursescompleted;
        $stats['activitiescomplete'] = $activitiescomplete;
        $stats['statsgeneralprogress'] = $activitiesprogress;


        // $stats['coursesenrolled'] = 2;
        // $stats['coursescompleted'] = 3;
        // $stats['activitiescomplete'] = 4;
        // $stats['statsgeneralprogress'] = 5;

        return json_encode($stats);
    }

    /**
     * Describes the get_smartch_stats return value
     * @return external_value
     */
    public static function get_smartch_stats_returns()
    {
        return new external_value(PARAM_RAW, 'User info in JSON Format');
        // return new \external_single_structure(
        //     array(
        //         // 'coursesenrolled' => new external_value(PARAM_INT, 'Enrolled Users'),
        //         // 'coursescompleted' => new external_value(PARAM_INT, 'Students Completed'),
        //         // 'activitiescompleted' => new external_value(PARAM_INT, 'Students Inprogress'),
        //         // 'activitiesdue' => new external_value(PARAM_INT, 'Students Not Started'),
        //         'username' => new external_value(PARAM_INT, 'inconnu')
        //     )
        // );
    }
}
