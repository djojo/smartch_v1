<?php

use tool_brickfield\local\areas\mod_choice\option;

require_once(__DIR__ . '/../../../config.php');
require_once('./utils.php');
// require_once($CFG->dirroot.'/enrol/cohort/lib.php');
// require_once($CFG->dirroot.'/group/lib.php');

require_login();
isPortailRH();
isAdminFormation();

global $USER, $DB, $CFG;

$cohortid = required_param('cohortid', PARAM_INT);
$cohort = $DB->get_record('cohort', ['id' => $cohortid]);
$courseid = optional_param('courseid', null, PARAM_INT);
$startdate = optional_param('startdate', null, PARAM_TEXT);
$enddate = optional_param('enddate', null, PARAM_TEXT);
$action = optional_param('action', null, PARAM_TEXT);
$messagesent = optional_param('messagesent', null, PARAM_INT);

// var_dump($startdate);
// die();


if($courseid && $action == "sync"){
    if(!$startdate || !$enddate){
        $messagenotif = "Vous devez rentrer des dates correctes.";
    } else {
        $course = $DB->get_record('course', ['id'=>$courseid]);
        //on sync la cohorte avec le cours
        syncCohortWithCourse($cohortid, $courseid, $startdate, $enddate);
        $messagenotif = $course->fullname . " est synchronisé avec le groupe";
    }
} else if($courseid && $action == "desync"){
    $course = $DB->get_record('course', ['id'=>$courseid]);
    //on desync la cohorte avec le cours
    desyncCohortWithCourse($cohortid, $courseid);
    $messagenotif = $course->fullname . " est désynchronisé avec le groupe";
    
} else if(!empty($messagesent)){
    $messagenotif = 'Message envoyé aux '.$messagesent.' membres du groupe';
}

$content = '';
$paginationtitle = '';
$prevurl = '';
$nexturl = '';

if($messagenotif){
    displayNotification($messagenotif);
}

$context = context_system::instance();
$PAGE->set_url(new moodle_url('/theme/remui/views/cohort.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title("Formations du groupe");

echo '<style>

#page{
    background:transparent !important;
}

#topofscroll {
    background: transparent !important;
    margin-top: 0px !important;
}

@media screen and (max-width: 830px) {
    #topofscroll{
        margin-top:40px !important;
    }
}

</style>';

echo $OUTPUT->header();



// echo html_writer::start_div('container');

$search = optional_param('search', '', PARAM_TEXT);
$pageno = optional_param('pageno', 1, PARAM_TEXT);

//le tableau des parametres pour la recherche et pagination
$params = array();
$filter = '';

// $param0['paramname'] = "courseid";
// $param0['paramvalue'] = $courseid;
// array_push($params, $param0);
// $filter = '&courseid=' . $courseid;


$param0['paramname'] = "cohortid";
$param0['paramvalue'] = $cohortid;
array_push($params, $param0);
$filter = '&cohortid=' . $cohortid;

if ($search != '') {
    $param1['paramname'] = "search";
    $param1['paramvalue'] = $search;
    array_push($params, $param1);
    $filter = '&search=' . $search;
}

$no_of_records_per_page = 5;
$offset = ($pageno - 1) * $no_of_records_per_page;

$filtersql = "";
if (!empty($search)) {
    $filtersql = ' AND c.fullname LIKE "%' . $search . '%"';
}

$querycourses = 'SELECT c.id, c.fullname as name, ss.startdate, ss.enddate
        FROM mdl_enrol e
        JOIN mdl_cohort co ON e.customint1 = co.id
        JOIN mdl_course c ON c.id = e.courseid
        JOIN mdl_smartch_session ss ON ss.groupid = e.customint2
        WHERE co.id = ' . $cohortid . '
        '.$filtersql.'
        LIMIT ' . $offset . ', ' . $no_of_records_per_page . '
        ';
$total_pages_sql = 'SELECT COUNT(*) count 
        FROM mdl_enrol e
        JOIN mdl_cohort co ON e.customint1 = co.id
        JOIN mdl_course c ON c.id = e.courseid
        WHERE co.id = ' . $cohortid . '
        '.$filtersql.'';


$courses = $DB->get_records_sql($querycourses, null);

// $allcourses = $DB->get_records('course', null);

$result = $DB->get_records_sql($total_pages_sql, null);
$total_rows = reset($result)->count;
$total_pages = ceil($total_rows / $no_of_records_per_page);

//le header avec bouton de retour au panneau admin
$templatecontextheader = (object)[
    'url' => new moodle_url('/theme/remui/views/cohorts.php'),
    'textcontent' => 'Retour aux groupes'
];
$content .= $OUTPUT->render_from_template('theme_remui/smartch_header_back', $templatecontextheader);

$content .= '<div class="row" style="margin:50px 0;"></div>';

$content .= '<div class="row">
<div class="col-md-12">
<h1 style="letter-spacing:1px;max-width:70%;cursor:pointer;" class="smartch_title FFF-Hero-Bold FFF-Blue">Groupe '.$cohort->name.'</h1>
</div>
</div>';

//le nombre de membres
$members = $DB->get_record_sql('SELECT COUNT(*) count 
FROM mdl_cohort co
JOIN mdl_cohort_members cm ON cm.cohortid = co.id
JOIN mdl_user u ON u.id = cm.userid
WHERE co.id = ' . $cohortid . '
AND u.deleted = 0 AND u.suspended = 0', null);

//le nombre de formations associés
$coursestotal = $DB->get_record_sql('SELECT COUNT(*) count 
FROM mdl_enrol e
JOIN mdl_cohort co ON e.customint1 = co.id
JOIN mdl_course c ON c.id = e.courseid
JOIN mdl_smartch_session ss ON ss.groupid = e.customint2
WHERE co.id = ' . $cohortid . '', null);


$content .= '<div class="row">
    <div class="col-md-12" style="display:flex;">
        <div onclick="location.href=\'' . new moodle_url('/theme/remui/views/cohortmembers.php?cohortid='.$cohortid) . '\'" class="smartch_box_link">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
            </svg>
            <div>'.$members->count.' membres</div>
        </div>
        <div class="smartch_box_link">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
            </svg>
            <div>'.$coursestotal->count.' formation</div>
        </div>
        <div style="display:none;" onclick="location.href=\'' . new moodle_url('/theme/remui/views/editcohort.php?cohortid='.$cohortid) . '\'" class="smartch_box_link">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
            </svg>
            <div>Modifier le groupe</div>
        </div>
        <div onclick="location.href=\'' . new moodle_url('/theme/remui/views/cohortmessage.php?cohortid='.$cohortid) . '\'" class="smartch_box_link">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
            </svg>
            <div>Envoyer un message</div>
        </div>
    </div>
</div>';

//barre de recherche des parcours
$templatecontext = (object)[
    'formurl' => new moodle_url('/theme/remui/views/cohort.php?cohortid=' . $cohortid),
    'textcontent' => 'Formations associés au groupe : '.$cohort->name,
    'lang_search' => "Rechercher",
    'params' => $params,
    'search' => $search
];
$content .= $OUTPUT->render_from_template('theme_remui/smartch_header_search', $templatecontext);


//La pagination
if (count($courses) == 0) {
    $paginationtitle .= 'Aucun résultat';
} else if (count($courses) == 1) {
    $paginationtitle .= '1 résultat';
} else {
    $paginationtitle .= $total_rows . ' résultats - page ' . $pageno . ' sur ' . $total_pages . '';
}
$paginationarray = range(1, $total_pages); // array(1, 2, 3)


if ($pageno == 1) {
    $previous = false;
} else {
    $previous = true;
    $newpage = $pageno - 1;
    $prevurl = new moodle_url('/theme/remui/views/cohort.php?pageno=' . $newpage) . $filter;
}

if ($pageno == $total_pages) {
    $next = false;
} else {
    $next = true;
    $newpage = $pageno + 1;
    $nexturl = new moodle_url('/theme/remui/views/cohort.php?pageno=' . $newpage) . $filter;
}

//la pagination en haut
$templatecontextpagination = (object)[
    'paginationtitle' => $paginationtitle,
    'search' => $search,
    'params' => $params,
    'total_rows' => $total_rows,
    'total_pages' => $total_pages,
    'pageno' => $pageno,
    'previous' => $previous,
    'next' => $next,
    'prevurl' => $prevurl,
    'nexturl' => $nexturl,
    'paginationarray' => array_values($paginationarray),
    'formurl' => new moodle_url('/theme/remui/views/cohort.php')
];

if (count($courses) > 0) {
    $content .= $OUTPUT->render_from_template('theme_remui/smartch_header_pagination', $templatecontextpagination);
} else {
    $content .= nothingtodisplay("Aucune formation associé...");
}

//affichage de la table de tous les utilisateurs
$content .= '<div class="row">
        <div class="col-sm-12 col-md-12 col-lg-12 col-xl-12">
            <table class="smartch_table">
                <thead style="display:none;">
                    <tr>
                        <th>Formation</th>
                        <th>Session</th>
                        <th>Date</th>
                        <th>Options</th>
                    </tr>
                </thead>
                <tbody>';
foreach ($courses as $course) {

    //on va chercher l'enrollement
    $enrol = $DB->get_record_sql('SELECT * 
    FROM mdl_enrol
    WHERE customint1 = ' . $cohort->id . '
    AND courseid = ' . $course->id, null);

    $cohortgroupid = $enrol->customint2;

    $content .= '<tr>
                    <td style="text-transform:capitalize;">
                        <svg width="50" height="50" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="24" cy="24" r="24" fill="#E2E8F0"/>
                            <path d="M28 19C28 21.2091 26.2091 23 24 23C21.7909 23 20 21.2091 20 19C20 16.7909 21.7909 15 24 15C26.2091 15 28 16.7909 28 19Z" stroke="#004687" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M24 26C20.134 26 17 29.134 17 33H31C31 29.134 27.866 26 24 26Z" stroke="#004687" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span style="margin-left: 10px;"><a href="' . new moodle_url('/theme/remui/views/adminteam.php') . '?teamid=' . $cohortgroupid . '">' . $course->name . '</a></span>
                    </td>
                    <td>
                        <a class="smartch_table_btn" href="' . new moodle_url('/theme/remui/views/adminteam.php') . '?teamid=' . $cohortgroupid . '">Voir la session</a>
                    </td>
                    <td>
                        Du ' . userdate($course->startdate, get_string('strftimedate')) . ' au ' . userdate($course->enddate, get_string('strftimedate')) . '
                    </td>
                    <td>
                        <a class="smartch_table_btn" href="' . new moodle_url('/theme/remui/views/cohort.php') . '?cohortid='.$cohortid.'&courseid=' . $course->id . '&action=desync">Supprimer l\'association du cours</a>
                    </td>
                </tr>';
}

$content .= '</tbody>
            </table>
        </div>
    </div>';

//la pagination en bas
if (count($courses) > 0) {
    $content .= $OUTPUT->render_from_template('theme_remui/smartch_header_pagination', $templatecontextpagination);
}

//on va chercher les formations qui ne sont pas lié au groupe
$nonlinkedcourses = $DB->get_records_sql('SELECT DISTINCT c.*
FROM mdl_course c
JOIN mdl_enrol e ON e.courseid <> c.id
WHERE c.fullname <> ""
AND format != "site"', null);


$content .= '<div class="row">';
$content .= '<div class="col-md-12">';
$content .= '<h3 style="letter-spacing:1px;max-width:70%;cursor:pointer;" class="smartch_title FFF-Hero-Bold FFF-Blue mt-5">Ajouter une formation au groupe : '.$cohort->name . '</h3>';
$content .= '<form class="mt-5" action="" method="post">';
$content .= '<div>';
$content .= '<label class="mr-2" for="startdate">Date de début</label>';
$content .= '<input value="'.date('Y-m-d').'" class="smartch_input mr-5" type="date" name="startdate"/>';
$content .= '<label class="mr-2" for="startdate">Date de fin</label>';
$content .= '<input value="'.date('Y-m-d', strtotime('+1 month')).'" class="smartch_input" type="date" name="enddate"/>';
$content .= '</div>';

$content .= '<div class="mt-5">';
$content .= '<label class="mr-2" for="startdate">Choisir une formation</label>';
$content .= '<input type="hidden" name="cohortid" value="'.$cohortid.'"/>';
$content .= '<input type="hidden" name="action" value="sync"/>';
$content .= '<select name="courseid" class="smartch_select">';
foreach($nonlinkedcourses as $course) {
    //On vérifie si il n'y a pas déjà un sync
    $inlist = $DB->get_record_sql('SELECT * 
    FROM mdl_course c
    JOIN mdl_enrol e ON e.courseid = c.id 
    WHERE e.courseid = ' . $course->id . '
    AND e.customint1 = ' . $cohortid, null);
    if(!$inlist){
        $content .= '<option value="'.$course->id.'">'.$course->fullname.'</option>';
    }
}
$content .= '</select>';
$content .= '</div>';

$content .= '<div style="text-align:left;">';
$content .= '<button class="smartch_btn" type="submit">Ajouter</button>';
$content .= '</div>';

$content .= '</form>';

$content .= '</div>'; //md12
$content .= '</div>'; //row

// $content .= html_writer::end_div(); //container

echo $content;

echo $OUTPUT->footer();

//pour la pagination
echo '<script>

// window.onload = function(){
//     var els = document.getElementsByClassName("page' . $pageno . '");
//     Array.from(els).forEach((el) => {
//         el.setAttribute("selected", "selected");
//     });
// };

    var els = document.getElementsByClassName("page' . $pageno . '");
    Array.from(els).forEach((el) => {
        el.setAttribute("selected", "selected");
    });

</script>';
