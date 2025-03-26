<?php
// This file is part of Moodle Course Rollover Plugin
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
 * @package     smartch
 * @author      Geoffroy Rouaix
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../config.php');
require_once('./utils.php');

require_login();

global $USER, $DB, $CFG;


$content = '';
$paginationtitle = '';
$prevurl = '';
$nexturl = '';

//On va chercher le rôle le plus haut de l'utilisateur
$rolename = getMainRole();

isAdminFormation();

$context = context_system::instance();
$PAGE->set_url(new moodle_url('/theme/remui/views/adminusers.php'));
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

if ($search != '') {
    $param1['paramname'] = "search";
    $param1['paramvalue'] = $search;
    array_push($params, $param1);
    $filter = '&search=' . $search;
}




// // si il y a une categorie de choisi
// if ($categoryid) {
//     if ($categoryid != "all") {
//         $filter .= ' AND cc.id = ' . $categoryid;
//         $allparams .= "&categoryid=" . $categoryid;
//         $nextparams .= "&categoryid=" . $categoryid;
//         //on rajoute au paramètres
//         $param1['paramname'] = "categoryid";
//         $param1['paramvalue'] = $categoryid;
//         array_push($params, $param1);
//     }
// }


$no_of_records_per_page = 10;
$offset = ($pageno - 1) * $no_of_records_per_page;

//on divise les requetes en fonction des rôles
if ($rolename == "super-admin" || $rolename == "manager") {
    if ($search != "") {
        $queryusers = 'SELECT * from mdl_user 
                WHERE email != "root@localhost"
                AND deleted = 0
                AND (lower(firstname) LIKE "%' . $search . '%" 
                OR lower(lastname) LIKE "%' . $search . '%"
                OR lower(username) LIKE "%' . $search . '%"
                OR concat(lower(firstname) , " " , lower(lastname)) LIKE "%' . $search . '%"
                OR lower(email) LIKE "%' . $search . '%")
                LIMIT ' . $offset . ', ' . $no_of_records_per_page;
        $total_pages_sql = 'SELECT COUNT(*) count FROM mdl_user 
                WHERE email != "root@localhost"
                AND deleted = 0
                AND (lower(firstname) LIKE "%' . $search . '%" 
                OR lower(lastname) LIKE "%' . $search . '%"
                OR concat(lower(firstname) , " " , lower(lastname)) LIKE "%' . $search . '%"
                OR lower(username) LIKE "%' . $search . '%"
                OR lower(email) LIKE "%' . $search . '%")';
    } else {
        $queryusers = 'SELECT * from mdl_user
                WHERE email != "root@localhost"
                AND deleted = 0
                LIMIT ' . $offset . ', ' . $no_of_records_per_page . '
                ';
        $total_pages_sql = 'SELECT COUNT(*) count FROM mdl_user WHERE email != "root@localhost" AND deleted = 0';
    }
    // } else if ($rolename == "smalleditingteacher" || $rolename == "editingteacher" || $rolename == "teacher") {
} else {
    //on prend seulement les utilisateurs de son groupe
    if ($search != "") {
        $queryusers = 'SELECT DISTINCT u.*
            FROM mdl_user u
            JOIN mdl_groups_members gm ON gm.userid = u.id
            WHERE gm.groupid IN (
                SELECT groupid
                FROM mdl_groups_members
                WHERE userid = ' . $USER->id . '
            )
            AND u.email != "root@localhost"
            AND u.deleted = 0
            AND (lower(u.firstname) LIKE "%' . $search . '%" 
            OR lower(u.lastname) LIKE "%' . $search . '%"
            OR lower(u.username) LIKE "%' . $search . '%"
            OR concat(lower(u.firstname) , " " , lower(u.lastname)) LIKE "%' . $search . '%"
            OR lower(u.email) LIKE "%' . $search . '%")
            LIMIT ' . $offset . ', ' . $no_of_records_per_page;
        $total_pages_sql = 'SELECT DISTINCT u.*
            FROM mdl_user u
            JOIN mdl_groups_members gm ON gm.userid = u.id
            WHERE gm.groupid IN (
                SELECT groupid
                FROM mdl_groups_members
                WHERE userid = ' . $USER->id . '
            )
            AND u.email != "root@localhost"
            AND u.deleted = 0
            AND (lower(u.firstname) LIKE "%' . $search . '%" 
            OR lower(u.lastname) LIKE "%' . $search . '%"
            OR lower(u.username) LIKE "%' . $search . '%"
            OR concat(lower(u.firstname) , " " , lower(u.lastname)) LIKE "%' . $search . '%"
            OR lower(u.email) LIKE "%' . $search . '%")';
    } else {
        $queryusers = 'SELECT DISTINCT u.*
            FROM mdl_user u
            JOIN mdl_groups_members gm ON gm.userid = u.id
            -- JOIN mdl_groups g ON g.id = gm.groupid
            WHERE gm.groupid IN (
                SELECT groupid
                FROM mdl_groups_members
                WHERE userid = ' . $USER->id . '
            )
            LIMIT ' . $offset . ', ' . $no_of_records_per_page;

        $total_pages_sql = 'SELECT DISTINCT COUNT(*) count FROM mdl_user u
            JOIN mdl_groups_members gm ON gm.userid = u.id
            WHERE gm.groupid IN (
                SELECT groupid
                FROM mdl_groups_members
                WHERE userid = ' . $USER->id . '
            )';
    }
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
    'formurl' => new moodle_url('/theme/remui/views/adminusers.php'),
    'textcontent' => "Tous les utilisateurs",
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
    $prevurl = new moodle_url('/theme/remui/views/adminusers.php?pageno=' . $newpage) . $filter;
}

if ($pageno == $total_pages) {
    $next = false;
} else {
    $next = true;
    $newpage = $pageno + 1;
    $nexturl = new moodle_url('/theme/remui/views/adminusers.php?pageno=' . $newpage) . $filter;
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
    'formurl' => new moodle_url('/theme/remui/views/adminusers.php')
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
                    <td>
                        <a class="smartch_table_btn" href="' . new moodle_url('/theme/remui/views/adminuser.php') . '?return=users&userid=' . $user->id . '">
                            <svg style="width:20px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>

                        </a>
                        <a class="smartch_table_btn ml-2" href="' . new moodle_url('/theme/remui/views/usermessage.php') . '?userid=' . $user->id . '&returnurl='.$PAGE->url.'">
                            <svg style="width:20px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                        </a>
                    </td>
                </tr>';
}

$content .= '</tbody>
            </table>
        </div>
    </div>';

//la pagination en bas
if (count($users) > 0) {
    $content .= $OUTPUT->render_from_template('theme_remui/smartch_header_pagination', $templatecontextpagination);
} else {
    $content .= nothingtodisplay("Aucun utilisateur trouvé");
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
