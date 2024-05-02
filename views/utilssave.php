<?php



function convert_to_string_time($time)
{
    $stringtime = "";

    $h = floor($time / 3600);
    $rh = $time % 3600;
    $m = floor($rh / 60);
    $s = $rh % 60;

    if ($h != 0) {
        $stringtime .= $h . "h ";
    }
    if ($m != 0) {
        $stringtime .= $m . "m ";
    }
    if ($s != 0) {
        $stringtime .= $s . "s";
    }
    if ($stringtime == "") {
        $stringtime = "0 min";
    }
    return $stringtime;
}

function get_total_time_spent_by_user_on_period($user_id, $period, $start, $end)
{
    if ($period == "lastweek") {
        $query = 'SELECT eal.timespent timespent
        FROM mdl_edwreports_activity_log eal
        WHERE eal.userid = ' . $user_id . ' AND eal.timestart >= ' . strtotime("-1 week");
    } else if ($period == "lastmonth") {
        $query = 'SELECT eal.timespent timespent
        FROM mdl_edwreports_activity_log eal
        WHERE eal.userid = ' . $user_id . ' AND eal.timestart >= ' . strtotime("-1 month");
    } else if ($period == "daterange") {
        $query = 'SELECT eal.timespent timespent
        FROM mdl_edwreports_activity_log eal
        WHERE eal.userid = ' . $user_id . ' AND eal.timestart >= ' . $start . ' AND eal.timestart <= ' . $end;
    } else {
        $query = 'SELECT eal.timespent timespent
        FROM mdl_edwreports_activity_log eal
        WHERE eal.userid = ' . $user_id;
    };

    global $DB;
    $params = null;

    $logs = $DB->get_records_sql($query, $params);

    $total = 0;
    foreach ($logs as $log) {
        $total += $log->timespent;
    }

    return convert_to_string_time($total);
}

function get_total_time_spent_by_user_by_course_on_period($user_id, $course_id, $period, $start, $end)
{
    if ($period == "lastweek") {
        $query = 'SELECT eal.timespent as timespent
        FROM mdl_edwreports_activity_log eal
        WHERE eal.userid = ' . $user_id . ' AND eal.course = ' . $course_id . ' AND eal.timestart >= ' . strtotime("-1 week");
    } else if ($period == "lastmonth") {
        $query = 'SELECT eal.timespent as timespent
        FROM mdl_edwreports_activity_log eal
        WHERE eal.userid = ' . $user_id . ' AND eal.course = ' . $course_id . ' AND eal.timestart >= ' . strtotime("-1 month");
    } else if ($period == "daterange") {
        $query = 'SELECT eal.timespent as timespent
        FROM mdl_edwreports_activity_log eal
        WHERE eal.userid = ' . $user_id . ' AND eal.course = ' . $course_id . ' AND eal.timestart >= ' . $start . ' AND eal.timestart <= ' . $end;
    } else {
        $query = 'SELECT eal.timespent as timespent
        FROM mdl_edwreports_activity_log eal
        WHERE eal.userid = ' . $user_id . ' AND eal.course = ' . $course_id;
    };

    global $DB;
    $params = null;

    $times = $DB->get_records_sql($query, $params);

    $total = 0;
    foreach ($times as $time) {
        $total += $time->timespent;
    }

    // $total = reset($arr)->timespent;

    return convert_to_string_time($total);
}

function get_all_time_spent_by_user_by_activity_on_period($user_id, $course_id, $activity_id, $period, $start, $end)
{
    if ($period == "lastweek") {
        $query = 'SELECT eal.timespent timespent, 
        eal.timestart AS "date"
        FROM mdl_edwreports_activity_log eal
        WHERE eal.userid = ' . $user_id . ' AND eal.course = ' . $course_id . ' AND eal.activity = ' . $activity_id . ' AND eal.timestart >= ' . strtotime("-1 week");
    } else if ($period == "lastmonth") {
        $query = 'SELECT eal.timespent timespent, 
        eal.timestart AS "date"
        FROM mdl_edwreports_activity_log eal
        WHERE eal.userid = ' . $user_id . ' AND eal.course = ' . $course_id . ' AND eal.activity = ' . $activity_id . ' AND eal.timestart >= ' . strtotime("-1 month");
    } else if ($period == "daterange") {
        $query = 'SELECT eal.timespent timespent, 
        eal.timestart AS "date"
        FROM mdl_edwreports_activity_log eal
        WHERE eal.userid = ' . $user_id . ' AND eal.course = ' . $course_id . ' AND eal.activity = ' . $activity_id . ' AND eal.timestart >= ' . $start . ' AND eal.timestart <= ' . $end;
    } else {
        $query = 'SELECT eal.timespent timespent, 
        eal.timestart AS "date"
        FROM mdl_edwreports_activity_log eal
        WHERE eal.userid = ' . $user_id . ' AND eal.course = ' . $course_id . ' AND eal.activity = ' . $activity_id;
    };

    global $DB;
    $params = null;

    $logs = $DB->get_records_sql($query, $params);

    return $logs;
}

function get_total_time_spent_by_user_by_activity_on_period($user_id, $course_id, $activity_id, $period, $start, $end)
{
    if ($period == "lastweek") {
        $query = 'SELECT sum(eal.timespent) timespent
        FROM mdl_edwreports_activity_log eal
        WHERE eal.userid = ' . $user_id . ' AND eal.course = ' . $course_id . ' AND eal.activity = ' . $activity_id . ' AND eal.timestart >= ' . strtotime("-1 week");
    } else if ($period == "lastmonth") {
        $query = 'SELECT sum(eal.timespent) timespent
        FROM mdl_edwreports_activity_log eal
        WHERE eal.userid = ' . $user_id . ' AND eal.course = ' . $course_id . ' AND eal.activity = ' . $activity_id . ' AND eal.timestart >= ' . strtotime("-1 month");
    } else if ($period == "daterange") {
        $query = 'SELECT sum(eal.timespent) timespent
        FROM mdl_edwreports_activity_log eal
        WHERE eal.userid = ' . $user_id . ' AND eal.course = ' . $course_id . ' AND eal.activity = ' . $activity_id . ' AND eal.timestart >= ' . $start . ' AND eal.timestart <= ' . $end;
    } else {
        $query = 'SELECT sum(eal.timespent) timespent
        FROM mdl_edwreports_activity_log eal
        WHERE eal.userid = ' . $user_id . ' AND eal.course = ' . $course_id . ' AND eal.activity = ' . $activity_id;
    };

    global $DB;
    $params = null;

    $arr = $DB->get_records_sql($query, $params);

    $total = reset($arr)->timespent;

    return convert_to_string_time($total);
}

function get_one_course_for_user($userid, $courseid)
{
    $querycourse = "SELECT c.fullname, c.summary, cc.name, c.id, complete.timecompleted, ra.timemodified
                FROM mdl_user u
                INNER JOIN mdl_role_assignments ra ON ra.userid = u.id
                INNER JOIN mdl_context ct ON ct.id = ra.contextid
                INNER JOIN mdl_course c ON c.id = ct.instanceid
                INNER JOIN mdl_role r ON r.id = ra.roleid
                INNER JOIN mdl_course_categories cc ON cc.id = c.category
                LEFT JOIN mdl_course_completions complete ON complete.course = c.id
                WHERE u.id  = " . $userid . " AND c.id = " . $courseid;
    global $DB;
    $params = null;
    $results = $DB->get_records_sql($querycourse, $params);
    return reset($results);
}

function get_total_time_spent_by_user_by_course($user_id, $course_id)
{
    $query = 'SELECT eal.timespent as timespent
    FROM mdl_edwreports_activity_log eal
    WHERE eal.userid = ' . $user_id . ' AND eal.course = ' . $course_id;

    global $DB;
    $params = null;

    $times = $DB->get_records_sql($query, $params);

    $total = 0;
    foreach ($times as $time) {
        $total += $time->timespent;
    }

    // $total = reset($arr)->timespent;

    return convert_to_string_time($total);
}

function get_all_users()
{

    global $DB;
    $params = null;
    // $users = $DB->get_records('user');

    $queryusers = 'SELECT * from mdl_user WHERE email != "contact@evolugo.fr" AND email != "root@localhost"';

    $users = $DB->get_records_sql($queryusers, $params);

    return $users;
}

function get_all_courses()
{
    global $DB;
    $params = null;
    $querycourses = "SELECT c.fullname AS 'coursename', c.id AS 'courseid'

            FROM mdl_course c

            WHERE c.format != 'site'

            AND c.visible = 1

            ORDER BY c.id
            ";

    return $DB->get_records_sql($querycourses, $params);
}

function get_average_grade_by_activity($activity_id)
{
    $query = 'SELECT g.rawgrade
    FROM mdl_grade_grades g
    WHERE g.itemid = ' . $activity_id;

    global $DB;
    $params = null;

    $grades = $DB->get_records_sql($query, $params);

    $total = 0;
    foreach ($grades as $grade) {
        $total += $grade;
    }

    $result = $total / count($grades);

    return $result;
}

function get_total_time_spent()
{
    $query = 'SELECT eal.timespent timespent
    FROM mdl_edwreports_activity_log eal';

    global $DB;
    $params = null;

    $logs = $DB->get_records_sql($query, $params);

    $total = 0;
    foreach ($logs as $log) {
        $total += $log->timespent;
    }

    return convert_to_string_time($total);
}

function get_total_time_spent_on_course($course_id)
{
    $query = 'SELECT eal.timespent timespent
    FROM mdl_edwreports_activity_log eal
    WHERE eal.course = ' . $course_id;

    global $DB;
    $params = null;

    $logs = $DB->get_records_sql($query, $params);

    $total = 0;
    foreach ($logs as $log) {
        $total += $log->timespent;
    }

    // $total = reset($arr)->timespent;

    return convert_to_string_time($total);
}

function get_time_spent_by_module($activity_id)
{
    $query = 'SELECT eal.timespent timespent
    FROM mdl_edwreports_activity_log eal
    WHERE eal.activity = ' . $activity_id;

    global $DB;
    $params = null;

    $logs = $DB->get_records_sql($query, $params);

    $total = 0;
    foreach ($logs as $log) {
        $total += $log->timespent;
    }

    // $total = reset($arr)->timespent;

    return convert_to_string_time($total);
}

function get_number_completed_by_module($activity_id)
{
    $query = 'SELECT *
            FROM mdl_course_modules_completion cmc
            WHERE cmc.coursemoduleid = ' . $activity_id;

    global $DB;
    $params = null;

    $nb_completion = $DB->get_records_sql($query, $params);

    return $nb_completion;
}

function get_number_completed_by_course($course_id)
{
    $query = 'SELECT *
            FROM mdl_course_completions cc
            WHERE cc.course = ' . $course_id;

    global $DB;
    $params = null;

    $nb_completion = $DB->get_records_sql($query, $params);

    return $nb_completion;
}

function count_module($course_id)
{
    $course_mods = get_course_mods($course_id);
    $result = count($course_mods);
    return $result;
}

function get_all_course_modules($course_id)
{
    global $DB;
    $course_mods = get_course_mods($course_id);
    $result = array();
    if ($course_mods) {
        foreach ($course_mods as $course_mod) {
            $course_mod->course_module_instance = $DB->get_record($course_mod->modname, array('id' => $course_mod->instance));
            $result[$course_mod->id] = $course_mod;
        }
    }
    return $result;
}

function get_all_user_by_course($course_id)
{
    $query = 'SELECT
    user2.firstname AS Firstname,
    user2.lastname AS Lastname,
    user2.email AS Email,
    user2.city AS City,
    course.fullname AS Course
    ,(SELECT shortname FROM mdl_role WHERE id=en.roleid) as Role
    ,(SELECT name FROM mdl_role WHERE id=en.roleid) as RoleName
    
    FROM mdl_course as course
    JOIN mdl_enrol AS en ON en.courseid = course.id
    JOIN mdl_user_enrolments AS ue ON ue.enrolid = en.id
    JOIN mdl_user AS user2 ON ue.userid = user2.id
    
    WHERE course.id =' . $course_id;

    global $DB;
    $params = null;

    return $DB->get_records_sql($query, $params);
}

function get_total_time_spent_by_user($user_id)
{
    $query = 'SELECT eal.timespent timespent
    FROM mdl_edwreports_activity_log eal
    WHERE eal.userid = ' . $user_id;

    global $DB;
    $params = null;

    $logs = $DB->get_records_sql($query, $params);

    $total = 0;
    foreach ($logs as $log) {
        $total += $log->timespent;
    }

    return convert_to_string_time($total);
}

function get_all_time_spent_by_user_by_activity($user_id, $course_id, $activity_id)
{
    $query = 'SELECT eal.timespent timespent, 
    eal.timestart AS "date"
    FROM mdl_edwreports_activity_log eal
    WHERE eal.userid = ' . $user_id . ' AND eal.course = ' . $course_id . ' AND eal.activity = ' . $activity_id;

    global $DB;
    $params = null;

    $logs = $DB->get_records_sql($query, $params);

    // $total = reset($arr)->timespent;

    // return convert_to_string_time($total);
    return $logs;
}

function get_total_time_spent_by_user_by_activity($user_id, $course_id, $activity_id)
{
    $query = 'SELECT sum(eal.timespent) timespent
    FROM mdl_edwreports_activity_log eal
    WHERE eal.userid = ' . $user_id . ' AND eal.course = ' . $course_id . ' AND eal.activity = ' . $activity_id;

    global $DB;
    $params = null;

    $arr = $DB->get_records_sql($query, $params);

    $total = reset($arr)->timespent;

    return convert_to_string_time($total);
}

function get_module_completion_by_user($user_id, $activity_id)
{
    $query = 'SELECT cmc.completionstate, cmc.viewed
    FROM mdl_course_modules_completion cmc
    WHERE cmc.userid = ' . $user_id . ' AND cmc.coursemoduleid = ' . $activity_id;

    global $DB;
    $params = null;

    $arr = $DB->get_records_sql($query, $params);

    if (reset($arr)->completionstate >= 1) {
        return "<span style='color:#4CAF50;'>Terminé</span>";
    } else if (reset($arr)->viewed == 1) {
        return "Vue";
    } else {
        return "<span style='color:#2196F3;'>A faire</span>";
    }
}


//le score des modules SCORM
function get_module_grade_by_user_scorm($user_id, $activity_id)
{
    $query = 'SELECT g.rawgrade
    FROM mdl_grade_grades g
    WHERE g.userid = ' . $user_id . ' AND g.itemid = ' . $activity_id;

    global $DB;
    $params = null;

    $arr = $DB->get_records_sql($query, $params);

    $result = reset($arr)->rawgrade;
    if ($result) {
        // return round($result);
        return floor($result);
    } else {
        return "";
    }
}
//le score des modules SCORM v2
function get_module_grade_by_user_scorm_V2($user_id, $activity_id)
{
    $query = 'SELECT gi.courseid, g.rawgrade, cm.id AS moduleid, gi.itemname AS modulename, gi.itemmodule
    FROM mdl_grade_items gi
        INNER JOIN mdl_grade_grades g ON gi.id = g.itemid
      INNER JOIN mdl_course_modules cm ON cm.course = gi.courseid AND cm.instance = gi.iteminstance
      INNER JOIN mdl_modules md ON cm.module = md.id AND md.name = gi.itemmodule
      WHERE gi.itemtype = "mod" AND g.userid = ' . $user_id . ' AND cm.id = ' . $activity_id;

    global $DB;
    $params = null;

    $results = $DB->get_records_sql($query, $params);
    $result = reset($results);
    $grade = $result->rawgrade;
    if ($grade) {
        if ($result->itemmodule == "quiz") {
            return floor($grade) . "/10";
        } else if ($result->modulename == "Quiz" && $result->itemmodule == "scorm") {
            return floor($grade) . "/100";
        } else if ($result->itemmodule == "scorm") {
            //telelangue
            return floor($grade);
        } else {
            return "";
        }
        // return round($result);

    } else {
        return "";
    }
}

//le score des modules QUIZ
function get_module_grade_by_user_quiz($user_id, $activity_id)
{
    $query = 'SELECT * FROM mdl_quiz_attempts WHERE userid = ' . $user_id . ' AND quiz = ' . $activity_id;

    global $DB;
    $params = null;

    $arr = $DB->get_records_sql($query, $params);

    $result = reset($arr)->sumgrades;
    if ($result) {
        // return round($result);
        return floor($result);
    } else {
        return "";
    }
}

function get_final_grade_by_course_by_user($user_id, $course_id)
{
    $modules = get_all_course_modules($course_id);
    $numbermodule = 0;
    $sumgrade = 0;
    foreach ($modules as $module) {

        $resultgrademodule = get_module_grade_by_user_scorm($user_id, $module->id);
        if ($resultgrademodule != "") {
            $numbermodule++;
            $sumgrade += $resultgrademodule;
        }
    }
    if ($numbermodule != 0) {
        return "Score final : " . floor($sumgrade / $numbermodule);
    } else {
        return "Pas de score pour cette formation.";
    }
}

function get_course_completion_ratio($user_id, $course_id)
{

    $modules = get_all_course_modules($course_id);
    $completed = 0;
    $total = 0;
    foreach ($modules as $module) {
        $query = 'SELECT cmc.completionstate, cmc.viewed
        FROM mdl_course_modules_completion cmc
        WHERE cmc.userid = ' . $user_id . ' AND cmc.coursemoduleid = ' . $module->id;

        global $DB;
        $params = null;

        $arr = $DB->get_records_sql($query, $params);

        if (reset($arr)->completionstate >= 1) {
            $completed++;
        }
        $total++;
    }
    return $completed . "/" . $total;
}

function get_course_progression($user_id, $course_id)
{

    $modules = get_all_course_modules($course_id);
    $completed = 0;
    $total = 0;
    foreach ($modules as $module) {
        $query = 'SELECT cmc.completionstate, cmc.viewed
        FROM mdl_course_modules_completion cmc
        WHERE cmc.userid = ' . $user_id . ' AND cmc.coursemoduleid = ' . $module->id;

        global $DB;
        $params = null;

        $arr = $DB->get_records_sql($query, $params);

        if (reset($arr)->completionstate >= 1) {
            $completed++;
        }
        $total++;
    }
    if ($completed == 0) {
        return 0;
    } else {
        return floor(100 * $completed / $total);
    }
}

function get_grades_by_course($course_id, $user_id)
{
    $query = "SELECT u.firstname AS 'First' , u.lastname AS 'Last',
    u.firstname + ' ' + u.lastname AS 'Display Name',
    c.fullname AS 'course',
    gi.id AS 'activityid',
    cc.name AS 'category',
    
    CASE
      WHEN gi.itemtype = 'course'
       THEN c.fullname + ' Course Total'
      ELSE gi.itemname
    END AS 'activity',
    
    ROUND(gg.finalgrade,2) AS grade,
    FROM_UNIXTIME(gg.timemodified) AS Time
    
    FROM mdl_course AS c
    JOIN mdl_context AS ctx ON c.id = ctx.instanceid
    JOIN mdl_role_assignments AS ra ON ra.contextid = ctx.id
    JOIN mdl_user AS u ON u.id = ra.userid
    JOIN mdl_grade_grades AS gg ON gg.userid = u.id
    JOIN mdl_grade_items AS gi ON gi.id = gg.itemid
    JOIN mdl_course_categories as cc ON cc.id = c.category
    
    WHERE  gi.courseid = c.id AND c.id = " . $course_id . " AND u.id = " . $user_id . "
    ORDER BY lastname";

    global $DB;
    $params = null;

    return $DB->get_records_sql($query, $params);
}

function get_user_courses($user_id)
{
    $query = "SELECT c.fullname, cc.name, c.id, complete.timecompleted, ra.timemodified, c.format
                FROM mdl_user u
                INNER JOIN mdl_role_assignments ra ON ra.userid = u.id
                INNER JOIN mdl_context ct ON ct.id = ra.contextid
                INNER JOIN mdl_course c ON c.id = ct.instanceid
                INNER JOIN mdl_role r ON r.id = ra.roleid
                INNER JOIN mdl_course_categories cc ON cc.id = c.category
                LEFT JOIN mdl_course_completions complete ON complete.course = c.id
                WHERE c.visible = 1 AND u.id  = " . $user_id;
    global $DB;
    $params = null;
    return $DB->get_records_sql($query, $params);
}
