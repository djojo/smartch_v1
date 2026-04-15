<?php

//on va chercher la formation 
$course = $DB->get_record('course', ['id' => $courseid]);

// on check si l'utilisateur est enrollé
if (!checkIfUserIsEnrolled($courseid, $userid)) {
    //on récupère l'id de la formation gratuite
    $catfree = $DB->get_record_sql('SELECT * from mdl_course_categories WHERE name = "Formation gratuite"', null);
    if ($course->category == $catfree->id) {
        //on va vers la page d'inscription
        redirect(new moodle_url('/theme/remui/views/subscribe.php?courseid=' . $courseid));
    } else {
        //on redirige vers l'accueil
        redirect(new moodle_url('/'));
    }
}


$templatecontextheader = (object)[
    'url' => new moodle_url('/my'),
    'textcontent' => 'Retour au tableau de bord'
];
$content .= $OUTPUT->render_from_template('theme_remui/smartch_header_back', $templatecontextheader);
// require_once('./returns.php');

//le context du template header pour le retour
$templatecontextheader = (object)[
    'url' => new moodle_url('/'),
    'coursename' => $course->fullname,
    'textcontent' => 'Retour au tableau de bord'

];
$content .= $OUTPUT->render_from_template('theme_remui/smartch_course_header', $templatecontextheader);

//On regarde si il y une session dans l'url
$sessionid = optional_param('sessionid', null, PARAM_INT);

if(!$sessionid){
    $groups = $DB->get_records_sql('SELECT g.id, g.name FROM mdl_groups g
                                    JOIN mdl_groups_members gm ON gm.groupid = g.id
                                    WHERE gm.userid = ' . $USER->id . ' AND g.courseid = ' . $courseid, null);
} else {
    $groups = $DB->get_records_sql('SELECT g.id, g.name FROM mdl_groups g
                                    JOIN mdl_smartch_session ss ON ss.groupid = g.id            
                                    JOIN mdl_groups_members gm ON gm.groupid = g.id
                                    WHERE gm.userid = ' . $USER->id . ' AND g.courseid = ' . $courseid . ' AND ss.id = ' . $sessionid, null);
}

//si l'utilisateur à un groupe
if (count($groups) > 0) {

    $group = reset($groups);
    $groupid = $group->id;

    // $content .= '<p style="color:white;">Il a un groupe...' . $group->id . '</p>';

    //on va chercher les informations de session 
    $session = $DB->get_record('smartch_session', ['groupid' => $group->id]);

    if ($session) {

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
        } else {
            $hassessionadress = true;
        }

        $sessiondate = 'Du ' . userdate($session->startdate, '%d/%m/%Y') . ' au ' . userdate($session->enddate, '%d/%m/%Y');

        //On va chercher le responsable pédagogique
        $coach = getResponsablePedagogique($groupid, $courseid, $session->id);

        // var_dump($coach);

        

        if ($coach[1]) {
            // $backurl = $_SERVER['REQUEST_URI'];
            $urlmessageresponsable = new moodle_url('/theme/remui/views/adminusermessage.php?userid=' . $coach[1]->id) . '&courseid=' . $courseid;
        }

        //le context du template du parcours
        $templatecontextcourse = (object)[
            'course' => $course,
            'iscoach' => true,
            'coach' => $coach[0],
            'urlmessageresponsable' => $urlmessageresponsable,
            'coursesummary' => html_entity_decode($course->summary),
            'session' => true,
            'teamname' => $group->name,
            'ligue' => $session->location,
            'sessionadress' => $sessionadress,
            'hassessionadress' => $hassessionadress,
            'sessiondate' => $sessiondate,
            'courseduration' => $courseduration,
            'coursetype' => $coursetype,
            'diplome' => $diplome,
            'category' => $category->name,
            'format' => "fff-course-box-info"
        ];
    } else {
        // $content .= '<p style="color:white;">Mais pas de session </p>';
        //le context du template du parcours
        $templatecontextcourse = (object)[
            'course' => $course,
            'session' => false,
            'coursesummary' => html_entity_decode($course->summary),
            'format' => "fff-course-box-info"
        ];
    }
} else {

    $portail = getConfigPortail();
    if ($portail == "portailrh") {

        //get the admin user with the role manager
        $adminUser = getManagerPortailRH();

        $urlmessageadmin = new moodle_url('/theme/remui/views/usermessage.php?userid=' . $adminUser->id) . '&returnurl=' . $PAGE->url;

        $templatecontextcourse = (object)[
            'iscoach' => true,
            'adminmanager' => $adminUser->firstname . ' ' . $adminUser->lastname,
            'urlmessageresponsable' => $urlmessageadmin,
            'course' => $course,
            'session' => false,
            'coursesummary' => html_entity_decode($course->summary),
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


    
}



//la présentation du parcours
$content .= $OUTPUT->render_from_template('theme_remui/smartch_course_info', $templatecontextcourse);

//on va chercher les stats
$sessionid = null;

if ($session) {
    $sessionid = $session->id;
}

//on va chercher les logs de l'utilisateur
$logs = $DB->get_records_sql('SELECT * FROM mdl_smartch_activity_log WHERE course = ' . $courseid . ' AND userid = ' . $USER->id, null);

$timetotal = 0;
foreach ($logs as $log) {
    $timetotal += $log->timespent;
}

$timespent = convert_to_string_time($timetotal);

// Whitelist + exclusion par nom (cohérent avec adminteam.php)
$trackedActivityTypes = "'scorm','assign','resource','feedback','quiz','page','url','lesson','bigbluebuttonbn','book','data','forum','h5pactivity'";
$excludedActivityNamesMetrics = ['Dossier de ligue', 'Devoir'];
$excludedNamesQuoted = implode(',', array_map(function($n) use ($DB) {
    return "'" . $DB->sql_like_escape($n) . "'";
}, $excludedActivityNamesMetrics));
$excludedByNameRows = $DB->get_records_sql("
    SELECT cm.id FROM mdl_course_modules cm
    JOIN mdl_modules m ON m.id = cm.module
    LEFT JOIN (
        SELECT id, name, 'assign' as acttype FROM mdl_assign
        UNION ALL SELECT id, name, 'resource' FROM mdl_resource
        UNION ALL SELECT id, name, 'feedback' FROM mdl_feedback
        UNION ALL SELECT id, name, 'quiz' FROM mdl_quiz
        UNION ALL SELECT id, name, 'scorm' FROM mdl_scorm
        UNION ALL SELECT id, name, 'h5pactivity' FROM mdl_h5pactivity
        UNION ALL SELECT id, name, 'bigbluebuttonbn' FROM mdl_bigbluebuttonbn
        UNION ALL SELECT id, name, 'page' FROM mdl_page
        UNION ALL SELECT id, name, 'url' FROM mdl_url
        UNION ALL SELECT id, name, 'book' FROM mdl_book
        UNION ALL SELECT id, name, 'lesson' FROM mdl_lesson
        UNION ALL SELECT id, name, 'data' FROM mdl_data
    ) act ON act.id = cm.instance AND act.acttype = m.name
    WHERE cm.course = " . intval($courseid) . "
    AND act.name IN (" . $excludedNamesQuoted . ")
", null);
$excludedByNameSql = !empty($excludedByNameRows)
    ? implode(',', array_map('intval', array_keys($excludedByNameRows)))
    : '0';

// e-learning : whitelist + exclusion par nom + deletioninprogress=0
$totalElearning = (int) $DB->count_records_sql(
    'SELECT COUNT(cm.id) FROM mdl_course_modules cm
     JOIN mdl_modules m ON m.id = cm.module
     WHERE cm.course = ? AND cm.completion > 0
     AND cm.deletioninprogress = 0
     AND m.name IN (' . $trackedActivityTypes . ')
     AND cm.id NOT IN (' . $excludedByNameSql . ')',
    [$courseid]
);
$finishedElearning = (int) $DB->count_records_sql(
    'SELECT COUNT(DISTINCT cm.id) FROM mdl_course_modules_completion cmc
     JOIN mdl_course_modules cm ON cm.id = cmc.coursemoduleid
     JOIN mdl_modules m ON m.id = cm.module
     WHERE cmc.userid = ? AND cm.course = ? AND cm.completion > 0
     AND cm.deletioninprogress = 0
     AND m.name IN (' . $trackedActivityTypes . ')
     AND cm.id NOT IN (' . $excludedByNameSql . ')
     AND cmc.completionstate >= 1',
    [$USER->id, $courseid]
);

// face2face : MIN(nb_plannings, nb_face2face) par section + completions réelles
$totalFace2face = 0;
$finishedFace2face = 0;
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
        [$courseid, $session->id]
    );
    foreach ($sectionStats as $s) {
        $totalFace2face += min($s->nb_plannings, $s->nb_face2face);
    }

    $sectionFinished = $DB->get_records_sql(
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
        [$courseid, $USER->id, $session->id]
    );
    foreach ($sectionFinished as $s) {
        $finishedFace2face += min($s->nb_plannings, $s->nb_face2face);
    }
}

$modulesfinished = $finishedElearning + $finishedFace2face;
$modulestocome = max(0, ($totalElearning + $totalFace2face) - $modulesfinished);

$templatecontextstats = (object)[
    'title1' => 'Votre ',
    'title2' => 'score',
    'timespent' => $timespent,
    'progress' => getCompletionPourcent($courseid, $USER->id, $groupid ?? null),
    'modulesfinished' => $modulesfinished,
    'modulestocome' => $modulestocome
];
//le score de l'étudiant sur ce cours
$content .= $OUTPUT->render_from_template('theme_remui/smartch_course_your_score', $templatecontextstats);


require_once('./courses_modules.php');




