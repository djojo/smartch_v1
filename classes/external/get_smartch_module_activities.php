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
trait get_smartch_module_activities
{
    /**
     * Describes the parameters for get_course_stats
     * @return external_function_parameters
     */
    public static function get_smartch_module_activities_parameters()
    {
        return new external_function_parameters(
            array(
                'moduleid' => new external_value(PARAM_INT, 'Module Id'),
            )
        );
    }

    /**
     * Save order of sections in array of configuration format
     * @param  int $courseid Course id
     * @return boolean       true
     */
    public static function get_smartch_module_activities($moduleid)
    {
        global $PAGE;
        // Validation for context is needed.
        $context = \context_course::instance($moduleid);
        self::validate_context($context);
        // $course = get_course($courseid);
        // $coursehandler = new \theme_remui_coursehandler();
        // $stats = $coursehandler->get_course_stats($course);

        return json_encode($moduleid);
    }

    /**
     * Describes the get_course_stats return value
     * @return external_value
     */
    public static function get_smartch_module_activities_returns()
    {
        return new external_value(PARAM_RAW, 'Activities of a module in JSON Format');
        // return new \external_single_structure(
        //     array(
        //         'enrolledusers' => new external_value(PARAM_INT, 'Enrolled Users'),
        //         'completed' => new external_value(PARAM_INT, 'Students Completed'),
        //         'inprogress' => new external_value(PARAM_INT, 'Students Inprogress'),
        //         'notstarted' => new external_value(PARAM_INT, 'Students Not Started')
        //     )
        // );
    }
}
