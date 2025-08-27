<?php

require_once(__DIR__ . '/../../../config.php');
require_once('./utils.php');


require_login();

global $USER, $DB, $CFG;

$courseid = optional_param('courseid', null, PARAM_INT);
$return = optional_param('return', null, PARAM_TEXT);

$search = optional_param('search', '', PARAM_TEXT);
$pageno = optional_param('pageno', 1, PARAM_TEXT);

//le tableau des parametres pour la recherche et pagination
$params = array();
$filter = '';
$content = '';
$paginationtitle = '';
$prevurl = '';
$nexturl = '';
$filterCourseJOIN = '';
$filterCourseWHERE = '';


//On va chercher le rôle le plus haut de l'utilisateur
$rolename = getMainRole();

isStudent();

$context = context_system::instance();
$PAGE->set_url(new moodle_url('/theme/remui/views/adminteams.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title("Tous les groupes");

echo '<style>

.select2-dropdown{
    width: 250px !important;
}

.select2-container {
    width: auto !important;
    margin-bottom: 10px !important;
}

.select2-container--default .select2-selection--single{
    border:none !important;
    
}
.select2-container--default .select2-selection--single .select2-selection__rendered{
    font-size: 1.25rem;
    color: #0b4785 !important;
    line-height: normal !important;
    font-family: "FFF-Equipe-Bold";
}

.select2-container--default .select2-results__option--highlighted.select2-results__option--selectable{
    background: #0b4785 !important;
}

.select2-container .select2-selection--single{
    height: auto !important;
}

.select2-container--default .select2-selection--single .select2-selection__arrow{
    top: 4px !important;
    right: -10px !important;
}

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

if(!empty($courseid)){
    $param1['paramname'] = "courseid";
    $param1['paramvalue'] = $courseid;
    array_push($params, $param1);
    $filter .= '&courseid=' . $courseid;
}

if(!empty($return)){
    $param1['paramname'] = "return";
    $param1['paramvalue'] = $return;
    array_push($params, $param1);
    $filter .= '&return=' . $return;
}

if (!empty($search)) {
    $param1['paramname'] = "search";
    $param1['paramvalue'] = $search;
    array_push($params, $param1);
    $filter .= '&search=' . $search;
}

$filteradmin = '';

if ($rolename == "super-admin" || $rolename == "manager") {
    
} else {
    if (!empty($search)) {
        $filteradmin = ' AND u.id = ' . $USER->id . '';
    } else {
        $filteradmin = 'JOIN mdl_groups_members gm ON gm.groupid = g.id
        JOIN mdl_user u ON u.id = gm.userid
        WHERE u.id = ' . $USER->id . '';
    }
}


if($courseid){
    $filteradmin .= ' AND g.courseid = '. $courseid . ' ';
}



$no_of_records_per_page = 4;
$offset = ($pageno - 1) * $no_of_records_per_page;

if (!empty($search)) {
    $search = trim(strtolower($search));
    $querygroups = 'SELECT DISTINCT g.id as id, c.id as courseid, c.shortname as coursename, g.name as groupname, g.courseid as courseid, COALESCE(ss.startdate, 0) as startdate
        FROM mdl_groups g
        JOIN mdl_course c ON c.id = g.courseid
        JOIN mdl_groups_members gm ON gm.groupid = g.id
        JOIN mdl_user u ON u.id = gm.userid
        LEFT JOIN mdl_smartch_session ss ON ss.groupid = g.id
        WHERE (lower(g.name) LIKE "%' . $search . '%" 
        OR lower(c.shortname) LIKE "%' . $search . '%"
        OR lower(u.email) LIKE "%' . $search . '%"
        OR concat(lower(u.firstname) , " " , lower(u.lastname)) LIKE "%' . $search . '%"
        OR lower(u.firstname) LIKE "%' . $search . '%"
        OR lower(u.lastname) LIKE "%' . $search . '%")
        ' . $filteradmin . '
        ORDER BY startdate DESC, g.id DESC
        LIMIT ' . $offset . ', ' . $no_of_records_per_page;
    $total_pages_sql = 'SELECT g.id, COUNT(*) count FROM mdl_groups g
        JOIN mdl_course c ON c.id = g.courseid
        JOIN mdl_groups_members gm ON gm.groupid = g.id
        JOIN mdl_user u ON u.id = gm.userid
        WHERE (lower(g.name) LIKE "%' . $search . '%" 
        OR lower(c.shortname) LIKE "%' . $search . '%"
        OR lower(u.email) LIKE "%' . $search . '%"
        OR concat(lower(u.firstname) , " " , lower(u.lastname)) LIKE "%' . $search . '%"
        OR lower(u.firstname) LIKE "%' . $search . '%"
        OR lower(u.lastname) LIKE "%' . $search . '%")
        ' . $filteradmin;
} else {
    $querygroups = 'SELECT g.id as id, c.id as courseid, c.shortname as coursename, g.name as groupname, g.courseid as courseid, COALESCE(ss.startdate, 0) as startdate
        FROM mdl_groups g
        JOIN mdl_course c ON c.id = g.courseid
        LEFT JOIN mdl_smartch_session ss ON ss.groupid = g.id
        ' . $filteradmin . '
        ORDER BY startdate DESC, g.id DESC
        LIMIT ' . $offset . ', ' . $no_of_records_per_page . '
        ';
    $total_pages_sql = 'SELECT COUNT(*) count 
        FROM mdl_groups g
        JOIN mdl_course c ON c.id = g.courseid
        ' . $filteradmin . '';
}


$groups = $DB->get_records_sql($querygroups, null);
$allgroups = $DB->get_records('groups', null);

$result = $DB->get_records_sql($total_pages_sql, null);
$total_rows = reset($result)->count;
$total_pages = ceil($total_rows / $no_of_records_per_page);

if($return == "course"){
    //le header avec bouton de retour au panneau admin
    $templatecontextheader = (object)[
        'url' => new moodle_url('/theme/remui/views/formation.php?id='.$courseid),
        'textcontent' => 'Retour au parcours'
    ];
    $content .= $OUTPUT->render_from_template('theme_remui/smartch_header_back', $templatecontextheader);
} else {
    //le header avec bouton de retour au panneau admin
    $templatecontextheader = (object)[
    'url' => new moodle_url('/theme/remui/views/adminmenu.php'),
    'textcontent' => 'Retour au panneau d\'administration'
    ];
    $content .= $OUTPUT->render_from_template('theme_remui/smartch_header_back', $templatecontextheader);
}

$content .= '<div class="row" style="margin:55px 0;"></div>';


if($rolename == "super-admin" || $rolename == "manager" || $rolename == "smalleditingteacher"){

    //le select des groupes
    $content .= '<div class="row">';
    $content .= '<div class="col-sm-12 col-md-12 col-lg-12 col-xl-12">';
    $content .= '<form style="display: inline;" id="search-form" method="get" action="'.new moodle_url('/theme/remui/views/adminteams.php').'">';
    $content .= '<div class="smartch_flex_mobile" style="justify-content: space-between;align-items: center;">';
    $content .= '<select class="select2 smartch_select" name="courseid" onchange="this.form.submit();">';
    $content .= '<option>Tous les Groupes</option>';

    $filterCourse = '';
    if($rolename == "smalleditingteacher"){
        $filterCourseJOIN = ' JOIN mdl_groups g ON c.id = g.courseid 
        JOIN mdl_groups_members gm ON gm.groupid = g.id
        JOIN mdl_user u ON u.id = gm.userid ';
        $filterCourseWHERE = ' AND u.id = ' . $USER->id . ' ';
    }

    //On va chercher toutes les formations
    $allcourses = $DB->get_records_sql('SELECT c.id, c.fullname 
    FROM mdl_course c
    ' . $filterCourseJOIN . '
    WHERE c.format != "site" 
    AND c.visible = 1
    ' . $filterCourseWHERE, null);

    foreach ($allcourses as $onecourse) {
        if($onecourse->id == $courseid){
            $content .= '<option selected value="' . $onecourse->id . '">Groupes de ' . $onecourse->fullname . '</option>';
        } else {
            $content .= '<option value="' . $onecourse->id . '">Groupes de ' . $onecourse->fullname . '</option>';
        }
    } 
    $content .= '</select>';
                // <h2 style="letter-spacing:1px;max-width:70%;cursor:pointer;" onclick="location.href='{{formurl}}'" class="FFABold FFF-Blue">{{textcontent}}</h2> 
    $content .= '<div>';
    // $content .= '<input type="hidden" name="{{paramname}}" value="{{paramvalue}}"/>';
    $content .= '<input autocomplete="off" class="smartch_input_search" type="text" name="search" placeholder="Rechercher" value="'.$search.'" autocomplete="off"/>';
    $content .= '</div>';
    $content .= '</div>';
    $content .= '</form>';
    $content .= '</div>';
    $content .= '</div>';
} else {

    //barre de recherche des utilisateurs de l'équipe
    $templatecontext = (object)[
        'formurl' => new moodle_url('/theme/remui/views/adminteams.php'),
        'textcontent' => "Tous les groupes",
        'params' => $params,
        'lang_search' => "Rechercher",
        'search' => $search
    ];
    $content .= $OUTPUT->render_from_template('theme_remui/smartch_header_search', $templatecontext);

}


//La pagination
if (count($groups) == 0) {
    $paginationtitle .= 'Aucun résultat';
} else if (count($groups) == 1) {
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
    $prevurl = new moodle_url('/theme/remui/views/adminteams.php?pageno=' . $newpage);
    
    $prevurl .= $filter;
}

if ($pageno == $total_pages) {
    $next = false;
} else {
    $next = true;
    $newpage = $pageno + 1;
    $nexturl = new moodle_url('/theme/remui/views/adminteams.php?pageno=' . $newpage);
    
    $nexturl .= $filter;
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
    'formurl' => new moodle_url('/theme/remui/views/adminteams.php')
];
if (count($groups) > 0) {
    $content .= $OUTPUT->render_from_template('theme_remui/smartch_header_pagination', $templatecontextpagination);
}

if (count($groups) == 0) {
    $content .= nothingtodisplay("Il n'y a pas de groupe à afficher...");
}

$content .= '<div class="row">';
foreach ($groups as $group) {
    //on va chercher le cours du groupe
    // $course = $DB->get_record('course', ['id' => $group->courseid]);
    //on va chercher les membres de l'équipe
    // $teamates = $DB->get_records('groups_members', ['groupid' => $group->id], '', '*', 0, 6);
    // $allmates = $DB->get_records('groups_members', ['groupid' => $group->id]);


    $querymates = '
                SELECT DISTINCT u.id as userid, u.firstname, u.lastname, r.shortname, r.id as roleid
                FROM mdl_role_assignments AS ra 
                JOIN mdl_user_enrolments AS ue ON ra.userid = ue.userid 
                JOIN mdl_role AS r ON ra.roleid = r.id 
                JOIN mdl_context AS c ON c.id = ra.contextid 
                JOIN mdl_enrol AS e ON e.courseid = c.instanceid AND ue.enrolid = e.id 
                JOIN mdl_user u ON u.id = ue.userid
                JOIN mdl_groups_members gm ON u.id = gm.userid

                WHERE gm.groupid = ' . $group->id . ' 
                AND r.shortname = "student"
                LIMIT 0, 6
                ';
    $queryallmates = '
                SELECT DISTINCT u.id as userid, u.firstname, u.lastname, r.shortname, r.id as roleid
                FROM mdl_role_assignments AS ra 
                JOIN mdl_user_enrolments AS ue ON ra.userid = ue.userid 
                JOIN mdl_role AS r ON ra.roleid = r.id 
                JOIN mdl_context AS c ON c.id = ra.contextid 
                JOIN mdl_enrol AS e ON e.courseid = c.instanceid AND ue.enrolid = e.id 
                JOIN mdl_user u ON u.id = ue.userid
                JOIN mdl_groups_members gm ON u.id = gm.userid
                WHERE gm.groupid = ' . $group->id . ' 
                AND r.shortname = "student"
                ';
    $teamates = $DB->get_records_sql($querymates, null);
    $allmates = $DB->get_records_sql($queryallmates, null);

    $totalmates = count($allmates);
    $el['total'] = $totalmates;
    $el['teamates'] = $teamates;

    if ($el['total'] > 1) {
        $nbmembres = $el['total'] . ' membres';
    } else {
        $nbmembres = $el['total'] . ' membre';
    }

    $groupname = extraireNomEquipe($group->groupname);

    $content .= '
        <div class="col-md-6" style="padding: 0 20px;margin-bottom:20px;">
        <div class="row">
            <div class="col-md-12" style="display: flex;justify-content: space-between;padding: 20px 0;min-height: 110px;">
                
                <div style="max-width: 70%;">
                    <a href="' . new moodle_url('/theme/remui/views/adminteam.php?return=teams&teamid=' . $group->id) . '"><span class="fff-title-team">' . $groupname . '</span></a>
                </div>
                <div>
                    <span style="margin-right:10px;">' . $nbmembres . '</span>
                    <svg style="display:none;" onclick="window.location.href=\'' . new moodle_url('/theme/remui/views/adminteam.php?return=teams&teamid=' . $group->id) . '&message=1#sendmessageteam\'" style="cursor:pointer;" width="20" height="16" viewBox="0 0 20 16" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <path d="M1 4L8.8906 9.2604C9.5624 9.70827 10.4376 9.70827 11.1094 9.2604L19 4M3 15H17C18.1046 15 19 14.1046 19 13V3C19 1.89543 18.1046 1 17 1H3C1.89543 1 1 1.89543 1 3V13C1 14.1046 1.89543 15 3 15Z" stroke="#0B427C" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </div>
            </div>
        </div>';



    $content .= '<div class="row" style="min-height: 170px;">';

    if (count($teamates) == 0) {
        $content .= '<h3 class="no_member_to_display">Il n\'y a pas de membre dans ce groupe...</h3>';
    }

    foreach ($el['teamates'] as $mate) {
        $userteam = $DB->get_record('user', ['id' => $mate->userid]);
        if(!$userteam){
            continue;
        }
        $courseprog = getCompletionPourcent($group->courseid, $userteam->id);
        $content .= '<div class="col-sm-12 col-md-6 col-lg-4" style="padding: 15px 5px;">
                    <div onclick="window.location.href=\'' . new moodle_url('/theme/remui/views/adminuser.php?return=teams&userid=' . $userteam->id) . '\'" style="cursor:pointer;display: flex;justify-content: space-between;width: 100%;"> 
                        <div>
                            <svg width="56" height="56" viewBox="0 0 56 56" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <rect width="56" height="56" rx="4.2" fill="#EDF2F7"/>
                                <path d="M34.5354 20.2707C34.5354 23.6857 31.767 26.4541 28.3521 26.4541C24.9371 26.4541 22.1688 23.6857 22.1688 20.2707C22.1688 16.8558 24.9371 14.0874 28.3521 14.0874C31.767 14.0874 34.5354 16.8558 34.5354 20.2707Z" stroke="#CBD5E0" stroke-width="3.09167" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M28.3521 31.0916C22.3759 31.0916 17.5312 35.9362 17.5312 41.9124H39.1729C39.1729 35.9362 34.3283 31.0916 28.3521 31.0916Z" stroke="#CBD5E0" stroke-width="3.09167" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </div>
                        <div style="margin-left: 10px;width: 100%;">';
        $matenamestring =  $userteam->firstname . '<br>' . $userteam->lastname;
        if (strlen($userteam->lastname) > 15 || strlen($userteam->firstname) > 15 || strlen($userteam->firstname . $userteam->lastname) > 30) {
            $content .= '<div style="line-height: 17px;" class="matename FFF-Equipe-Regular" style="height: 50px;">
                                ' . $matenamestring . '
                            </div>';
        } else {
            $content .= '<div class="matename FFF-Equipe-Regular" style="height: 50px;">
                                ' . $matenamestring . '
                            </div>';
        }
        $content .= '<div class="smartch_progress_bar_box" style="width: 100%;">
                                <div class="smartch_progress_bar_mini">
                                    <div class="smartch_progress_bar_number"></div>
                                    <div class="smartch_progress_bar_gain" style="width:' . $courseprog . '% !important;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>';
    }

    $content .= '</div>';

    $content .= '
        
        <div class="row" style="margin-top:20px;">
            <div class="col-md-12">
                <a class="smartch_btn" href="' . new moodle_url('/theme/remui/views/adminteam.php?return=teams&teamid=' . $group->id) . '">Voir plus</a>
            </div>
        </div>
    </div>
    ';
}
$content .= '</div>';

//la pagination en bas
if (count($groups) > 0) {
    $content .= $OUTPUT->render_from_template('theme_remui/smartch_header_pagination', $templatecontextpagination);
}

// $content .= html_writer::end_div(); //container

echo $content;

echo $OUTPUT->footer();

//pour la pagination
echo '<script>

window.onload = function(){
    var els = document.getElementsByClassName("page' . $pageno . '");
    Array.from(els).forEach((el) => {
        el.setAttribute("selected", "selected");
    });
};


$(document).ready(function() {
    $(".select2").select2();
});

</script>';
