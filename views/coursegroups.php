<?php

require_once(__DIR__ . '/../../../config.php');
require_once('./utils.php');

require_login();

global $USER, $DB, $CFG;

$courseid = required_param('courseid', PARAM_INT);
$course = $DB->get_record('course', ['id' => $courseid]);
$content = '';
$paginationtitle = '';
$prevurl = '';
$nexturl = '';

//On va chercher le rôle le plus haut de l'utilisateur
$rolename = getMainRole();

isStudent();

$context = context_system::instance();
$PAGE->set_url(new moodle_url('/theme/remui/views/courseusers.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title("Utilisateurs");

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

//on divise les requetes en fonction des rôles
if ($rolename == "super-admin" || $rolename == "manager") {
    if ($search != "") {
        $queryusers = 'SELECT u.id, u.username, u.firstname, u.lastname, u.email
                FROM mdl_user u
                JOIN mdl_user_enrolments ue ON ue.userid = u.id
                JOIN mdl_enrol e ON e.id = ue.enrolid
                WHERE e.courseid = ' . $courseid . '
                AND (lower(u.firstname) LIKE "%' . $search . '%" 
                OR lower(u.lastname) LIKE "%' . $search . '%"
                OR lower(u.username) LIKE "%' . $search . '%"
                OR concat(lower(u.firstname) , " " , lower(u.lastname)) LIKE "%' . $search . '%"
                OR lower(u.email) LIKE "%' . $search . '%")
                LIMIT ' . $offset . ', ' . $no_of_records_per_page;
        $total_pages_sql = 'SELECT COUNT(*) count 
                FROM mdl_user u
                JOIN mdl_user_enrolments ue ON ue.userid = u.id
                JOIN mdl_enrol e ON e.id = ue.enrolid
                WHERE e.courseid = ' . $courseid . '
                AND (lower(u.firstname) LIKE "%' . $search . '%" 
                OR lower(u.lastname) LIKE "%' . $search . '%"
                OR concat(lower(u.firstname) , " " , lower(u.lastname)) LIKE "%' . $search . '%"
                OR lower(u.username) LIKE "%' . $search . '%"
                OR lower(u.email) LIKE "%' . $search . '%")';
    } else {
        $queryusers = 'SELECT u.id, u.username, u.firstname, u.lastname, u.email
                FROM mdl_user u
                JOIN mdl_user_enrolments ue ON ue.userid = u.id
                JOIN mdl_enrol e ON e.id = ue.enrolid
                WHERE e.courseid = ' . $courseid . '
                LIMIT ' . $offset . ', ' . $no_of_records_per_page . '
                ';
        $total_pages_sql = 'SELECT COUNT(*) count 
                FROM mdl_user u
                JOIN mdl_user_enrolments ue ON ue.userid = u.id
                JOIN mdl_enrol e ON e.id = ue.enrolid
                WHERE e.courseid = ' . $courseid;
    }
    // } else if ($rolename == "smalleditingteacher" || $rolename == "editingteacher" || $rolename == "teacher") {
}
//  else {
//     redirect(new moodle_url('/'));
// }

// $userid = 2; // Remplacez YOUR_USER_ID par l'ID de l'utilisateur concerné

// $sql = "SELECT u.*
//         FROM {user} u
//         JOIN {groups_members} gm ON gm.userid = u.id
//         JOIN {groups} g ON g.id = gm.groupid
//         WHERE gm.groupid IN (
//                 SELECT groupid
//                 FROM mdl_groups_members
//                 WHERE userid =  :userid
//             )";

// $users = $DB->get_records_sql($sql, ['userid' => $userid]);

$users = $DB->get_records_sql($queryusers, null);
// $users = $DB->get_recordset_sql($queryusers, null);
// var_dump($users);

$allusers = $DB->get_records('user', null);

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

//tous les utilisateurs
$allusers = array();

foreach ($users as $user) {
    $el['firstname'] = $user->firstname;
    $el['lastname'] = $user->lastname;
    $el['id'] = $user->id;
    $el['url'] = $CFG->wwwroot . "/user/view.php?id=" . $user->id;

    // //On va chercher l'image du cours
    // $course2 = new core_course_list_element($course);
    // foreach ($course2->get_course_overviewfiles() as $file) {
    //     if ($file->is_valid_image()) {
    //         $imagepath = '/' . $file->get_contextid() .
    //             '/' . $file->get_component() .
    //             '/' . $file->get_filearea() .
    //             $file->get_filepath() .
    //             $file->get_filename();
    //         $imageurl = file_encode_url(
    //             $CFG->wwwroot . '/pluginfile.php',
    //             $imagepath,
    //             false
    //         );
    //         $el['img'] = $imageurl;
    //         // Use the first image found.
    //         break;
    //     }
    // }
    array_push($allusers, $el);
}

//barre de recherche des parcours
$templatecontext = (object)[
    'formurl' => new moodle_url('/theme/remui/views/courseusers.php'),
    'textcontent' => "Apprenants dans la formation " . $course->fullname,
    'lang_search' => "Rechercher",
    'params' => $params,
    'search' => $search
];
$content .= $OUTPUT->render_from_template('theme_remui/smartch_header_search', $templatecontext);


//La pagination
if (count($users) == 0) {
    $paginationtitle .= 'Aucun résultat';
} else if (count($users) == 1) {
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
    $prevurl = new moodle_url('/theme/remui/views/courseusers.php?pageno=' . $newpage) . $filter;
}

if ($pageno == $total_pages) {
    $next = false;
} else {
    $next = true;
    $newpage = $pageno + 1;
    $nexturl = new moodle_url('/theme/remui/views/courseusers.php?pageno=' . $newpage) . $filter;
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
    'formurl' => new moodle_url('/theme/remui/views/courseusers.php')
];

if (count($users) > 0) {
    $content .= $OUTPUT->render_from_template('theme_remui/smartch_header_pagination', $templatecontextpagination);
}

//affichage de la table de tous les utilisateurs
$content .= '<div class="row">
        <div class="col-sm-12 col-md-12 col-lg-12 col-xl-12">
            <table class="smartch_table">
                <thead>
                    <tr>
                        <th>Utilisateur</th>
                        <th>Email</th>
                        <th>Dernier accès</th>
                        <th>Options</th>
                    </tr>
                </thead>
                <tbody>';
foreach ($users as $user) {

    if ($user->lastaccess == 0) {
        $lastaccess = "Jamais";
    } else {
        $lastaccess = userdate($user->lastaccess, get_string('strftimedate'));
    }

    $content .= '<tr>
                    <td style="text-transform:capitalize;">
                        <svg width="50" height="50" viewBox="0 0 48 48" fill="none" xmlns="http://www.w3.org/2000/svg">
                            <circle cx="24" cy="24" r="24" fill="#E2E8F0"/>
                            <path d="M28 19C28 21.2091 26.2091 23 24 23C21.7909 23 20 21.2091 20 19C20 16.7909 21.7909 15 24 15C26.2091 15 28 16.7909 28 19Z" stroke="#004687" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            <path d="M24 26C20.134 26 17 29.134 17 33H31C31 29.134 27.866 26 24 26Z" stroke="#004687" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        <span style="margin-left: 10px;"><a href="' . new moodle_url('/theme/remui/views/adminuser.php') . '?return=users&userid=' . $user->id . '">' . $user->firstname . ' ' . $user->lastname . '</a></span>
                    </td>
                    <td>' . $user->email . '</td>
                    <td>' . $lastaccess . '</td>
                    <td><a class="smartch_table_btn" href="' . new moodle_url('/theme/remui/views/adminuser.php') . '?return=users&userid=' . $user->id . '">Consulter</a></td>
                </tr>';
}

$content .= '</tbody>
            </table>
        </div>
    </div>';

//la pagination en bas
if (count($users) > 0) {
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
