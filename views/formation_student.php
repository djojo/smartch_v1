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
        $coach = getResponsablePedagogique($groupid, $courseid);

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

$modulesstatus = getModulesStatus($courseid, $sessionid);

//on va chercher les logs de l'utilisateur
$logs = $DB->get_records_sql('SELECT * FROM mdl_smartch_activity_log WHERE course = ' . $courseid . ' AND userid = ' . $USER->id, null);

$timetotal = 0;
foreach ($logs as $log) {
    $timetotal += $log->timespent;
}

$timespent = convert_to_string_time($timetotal);

$templatecontextstats = (object)[
    'title1' => 'Votre ',
    'title2' => 'score',
    'timespent' => $timespent,
    'progress' => getCompletionPourcent($courseid, $USER->id),
    'modulesfinished' => $modulesstatus[0],
    'modulestocome' => $modulesstatus[1]
];
//le score de l'étudiant sur ce cours
$content .= $OUTPUT->render_from_template('theme_remui/smartch_course_your_score', $templatecontextstats);


require_once('./courses_modules.php');




