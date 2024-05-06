<?php

require_once(__DIR__ . '/../../../config.php');
require_once('./utils.php');

require_login();
isPortailRH();

global $USER, $DB, $CFG;

// $courseid = required_param('courseid', PARAM_INT);
// $course = $DB->get_record('course', ['id' => $courseid]);
// var_dump($course);
// die();
$content = '';
$paginationtitle = '';
$prevurl = '';
$nexturl = '';

//On va chercher le rôle le plus haut de l'utilisateur
$rolename = getMainRole();

isStudent();

$context = context_system::instance();
$PAGE->set_url(new moodle_url('/theme/remui/views/cohorts.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title("Groupes");

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

$param0['paramname'] = "courseid";
$param0['paramvalue'] = $courseid;
array_push($params, $param0);
$filter = '&courseid=' . $courseid;

if ($search != '') {
    $param1['paramname'] = "search";
    $param1['paramvalue'] = $search;
    array_push($params, $param1);
    $filter = '&search=' . $search;
}

$no_of_records_per_page = 10;
$offset = ($pageno - 1) * $no_of_records_per_page;


if ($search != "") {
    $querycohorts = 'SELECT c.id, c.name
            FROM mdl_cohort c
            WHERE lower(c.name) LIKE "%' . $search . '%" 
            LIMIT ' . $offset . ', ' . $no_of_records_per_page;
    $total_pages_sql = 'SELECT COUNT(*) count 
            FROM mdl_cohort c
            WHERE lower(c.name) LIKE "%' . $search . '%"';
            echo $queryusers;
} else {
    $querycohorts = 'SELECT c.id, c.name
            FROM mdl_cohort c
            LIMIT ' . $offset . ', ' . $no_of_records_per_page . '
            ';
    $total_pages_sql = 'SELECT COUNT(*) count 
            FROM mdl_cohort c';
}

$cohorts = $DB->get_records_sql($querycohorts, null);

$allcohorts = $DB->get_records('cohort', null);

$result = $DB->get_records_sql($total_pages_sql, null);
$total_rows = reset($result)->count;
$total_pages = ceil($total_rows / $no_of_records_per_page);

//le header avec bouton de retour au panneau admin
$templatecontextheader = (object)[
    'url' => new moodle_url('/theme/remui/views/adminmenu.php'),
    'textcontent' => 'Retour au panneau d\'administration'
];
$content .= $OUTPUT->render_from_template('theme_remui/smartch_header_back', $templatecontextheader);

$content .= '<div class="row" style="margin:30px 0;"></div>';


//barre de recherche des parcours
$templatecontext = (object)[
    'formurl' => new moodle_url('/theme/remui/views/cohorts.php'),
    'textcontent' => "Groupes ",
    'lang_search' => "Rechercher",
    'params' => $params,
    'search' => $search
];
$content .= $OUTPUT->render_from_template('theme_remui/smartch_header_search', $templatecontext);


//La pagination
if (count($cohorts) == 0) {
    $paginationtitle .= 'Aucun résultat';
} else if (count($cohorts) == 1) {
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
    $prevurl = new moodle_url('/theme/remui/views/cohorts.php?pageno=' . $newpage) . $filter;
}

if ($pageno == $total_pages) {
    $next = false;
} else {
    $next = true;
    $newpage = $pageno + 1;
    $nexturl = new moodle_url('/theme/remui/views/cohorts.php?pageno=' . $newpage) . $filter;
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
    'formurl' => new moodle_url('/theme/remui/views/cohorts.php')
];

if (count($cohorts) > 0) {
    $content .= $OUTPUT->render_from_template('theme_remui/smartch_header_pagination', $templatecontextpagination);
}


$content .= '<div class="row mb-3">
    <div class="col-md-12">
    <a class="smartch_btn" href="'.new moodle_url('/theme/remui/views/createcohort.php').'">Nouveau groupe</a>
    </div>
</div>';

//affichage de la table de toutes les cohortes
$content .= '<div class="row">
        <div class="col-sm-12 col-md-12 col-lg-12 col-xl-12">
            <table class="smartch_table">
                <thead>
                    <tr>
                        <th>Nom</th>
                        <th>Utilisateurs</th>
                        <th>Formations</th>
                        <th>Options</th>
                    </tr>
                </thead>
                <tbody>';
foreach ($cohorts as $cohort) {

    //on compte le nombre d'utilisateurs dans la cohorte
    $users = $DB->get_record_sql('SELECT COUNT(*) count 
    FROM mdl_cohort c
    JOIN mdl_cohort_members cm ON cm.cohortid = c.id
    WHERE c.id = '. $cohort->id, null);

    $formations = $DB->get_record_sql('SELECT COUNT(*) count
    FROM mdl_enrol e
    JOIN mdl_cohort c ON e.customint1 = c.id
    WHERE e.enrol = "cohort"
    AND c.id = ' . $cohort->id, null);


    $content .= '<tr>
                    <td style="text-transform:capitalize;">
                        
                        <span style="margin-left: 10px;"><a href="' . new moodle_url('/theme/remui/views/cohort.php') . '?cohortid=' . $cohort->id . '">' . $cohort->name . '</a></span>
                    </td>
                    <td>'.$users->count.'</td>
                    <td>'.$formations->count.'</td>
                    <td>
                        <a class="smartch_table_btn" href="' . new moodle_url('/theme/remui/views/cohort.php') . '?cohortid=' . $cohort->id . '">
                            <svg style="width:20px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 7.74-3.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                            </svg>
                            Formations associés
                        </a>
                        <a class="smartch_table_btn ml-2" href="' . new moodle_url('/cohort/assign.php') . '?id=' . $cohort->id . '">
                            <svg style="width:20px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                            </svg>
                            Membres
                        </a>
                    </td>
                </tr>';
}

$content .= '</tbody>
            </table>
        </div>
    </div>';

//la pagination en bas
if (count($cohorts) > 0) {
    $content .= $OUTPUT->render_from_template('theme_remui/smartch_header_pagination', $templatecontextpagination);
}

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
