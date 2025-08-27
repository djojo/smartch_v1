<?php

//on va chercher la formation 
$course = $DB->get_record('course', ['id' => $courseid]);

//on regarde si on est en formation gratuite
$freecat = $DB->get_record_sql('SELECT * from mdl_course_categories WHERE name = "Formation gratuite"', null);
//si on est sur une formation gratuite
if ($course->category == $freecat->id) {
    $iscategoryfree = true;
}

//le context du template header pour le retour
$templatecontextheader = (object)[
    'url' => new moodle_url('/theme/remui/views/adminformations.php'),
    'coursename' => $course->fullname,
    // 'coursename' => $course->fullname . ' (Vue pour le ' . $rolename . ')',
    'textcontent' => 'Retour aux parcours'

];
$content .= $OUTPUT->render_from_template('theme_remui/smartch_course_header', $templatecontextheader);

$timeplus30 = time() + 30 * 24 * 60 * 60;
$timemoins30 = time() - 30 * 24 * 60 * 60;


//pas de limite car formateur, et trié par date de début de session
$groups = $DB->get_records_sql(
    'SELECT g.id, g.name FROM mdl_groups g
JOIN mdl_groups_members gm ON gm.groupid = g.id
JOIN mdl_smartch_session ss ON ss.groupid = g.id
WHERE g.courseid = ' . $courseid . '
AND gm.userid = ' . $USER->id . '
ORDER BY ss.startdate ASC',
    null
);


//on regarde si un utilisateur est séléctionné
$userid = optional_param('userid', null, PARAM_INT);

$groupid = optional_param('groupid', null, PARAM_INT);
// var_dump($groupid);
if ($groupid) {
    $group = $DB->get_record('groups', ['id' => $groupid]);
} else if ($userid) {
    //on va chercher le groupe de l'utilisateur séléctionné
    $groups = $DB->get_records_sql('SELECT g.id, g.name FROM mdl_groups g
    JOIN mdl_groups_members gm ON gm.groupid = g.id
    WHERE gm.userid = ' . $userid . ' AND g.courseid = ' . $courseid, null);
    $group = reset($groups);
    $groupid = $group->id; //pour le responsable pedagogique
}

//si on a au moins un groupe
if ($group) {

    //on va chercher les informations de session 
    $session = $DB->get_record('smartch_session', ['groupid' => $group->id]);

    if ($session) {
        $issession = true;
    } else{
        $issession = false;
    }

    $sessionadress = "";
    if ($session->adress1 != "") {
        $sessionadress .= $session->adress1;
    }
    if ($session->adress2 != "") {
        $sessionadress .= ', ' . $session->adress2;
    }
    if ($session->zip != "") {
        $sessionadress .= ', ' . $session->zip;
    }
    if ($session->city != "") {
        $sessionadress .= ', ' . $session->city;
    }

    if ($sessionadress == "") {
        $hassessionadress = false;
    }

    $sessiondate = 'Du ' . userdate($session->startdate, '%d/%m/%Y') . ' au ' . userdate($session->enddate, '%d/%m/%Y');

    //On va chercher le responsable pédagogique
    $coach = getResponsablePedagogique($groupid, $courseid, $session->id);

    if ($coach[1]) {
        // $backurl = $_SERVER['REQUEST_URI'];
        $urlmessageresponsable = new moodle_url('/theme/remui/views/adminusermessage.php?userid=' . $coach[1]->id) . '&courseid=' . $courseid;
    }

    //le context du template du parcours
    $templatecontextcourse = (object)[
        'course' => $course,
        'urlmessageresponsable' => $urlmessageresponsable,
        'coursesummary' => html_entity_decode($course->summary),
        'session' => $issession,
        'teamname' => $group->name,
        'ligue' => $session->location,
        'sessionadress' => $sessionadress,
        'hassessionadress' => $hassessionadress,
        'sessiondate' => $sessiondate,
        'coach' => $coach[0],
        'category' => $category->name,
        'format' => "fff-course-box-info"
    ];
} else {
    //le context du template du parcours
    $templatecontextcourse = (object)[
        'course' => $course,
        'session' => false,
        'coursesummary' => html_entity_decode($course->summary),
        'format' => "fff-course-box-info"
    ];
}

//la présentation du parcours
$content .= $OUTPUT->render_from_template('theme_remui/smartch_course_info', $templatecontextcourse);

//les groupes
if(!$iscategoryfree){
    if($rolename == "smalleditingteacher"){

        $totalteam = count($groups);
        $s = '';

        if ($totalteam > 1) {
            $s = 's';
        }
        
        $content .= '<h3 class="FFF-title1" style="display: flex;align-items: center;margin-top:50px;">
        <span class="FFF-Hero-Black FFF-Blue" style="margin-right:10px;">' . $totalteam . '</span><span style="letter-spacing:1px;" class="FFF-Hero-Black FFF-Gold">groupe' . $s . '</span>  
        </h3>';

        if ($totalteam == 0) {
            $content .= nothingtodisplay("Vous n'avez pas de groupe sur ce parcours");
        } else {
            $content .= '<div class="row mt-3">';
            $content .= '<div>';
            $content .= '<a class="smartch_btn gap-2" href="'.new moodle_url("/theme/remui/views/adminteams.php").'?courseid='.$courseid.'&return=course">
            Voir tous les groupes de ce parcours
            <svg style="width:20px;height:20px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="m12.75 15 3-3m0 0-3-3m3 3h-7.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            </a>';
            $content .= '</div>';
            $content .= '</div>';
        }
    }else{
        require_once('./includes_groups.php');
    }
}

if ($group) {

    //on va chercher les logs du groupe
    $logs = $DB->get_records_sql('SELECT sa.id, sa.timespent FROM mdl_smartch_activity_log sa
    JOIN mdl_groups_members gm ON gm.userid = sa.userid
    WHERE sa.course = ' . $courseid . ' AND gm.groupid =  ' . $group->id, null);

    $timetotal = 0;
    foreach ($logs as $log) {
        $timetotal += $log->timespent;
    }

    $timespent = convert_to_string_time($timetotal);

    //on va chercher les stats
    $progress = getTeamProgress($courseid, $group->id);

    $templatecontextstats = (object)[
        'timespent' => $timespent,
        'progress' => $progress[0],
        'progressmax' => $progress[1],
        'progressmin' => $progress[2]
    ];
    //les stats sur ce groupe
    $content .= $OUTPUT->render_from_template('theme_remui/smartch_team_score', $templatecontextstats);
}

require_once('./courses_modules.php');
