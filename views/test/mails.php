<?php

require_once(__DIR__ . '/../../../../config.php');
require_once('../utils.php');

require_login();

global $USER, $DB, $CFG;

$search = optional_param('search', '', PARAM_TEXT);
$pageno = optional_param('pageno', 1, PARAM_TEXT);
$action = optional_param('action', null, PARAM_TEXT);
$templateid = optional_param('templateid', null, PARAM_TEXT);

if($action == 'delete' && !empty($templateid)){
    $DB->delete_records('smartch_mail_templates', array('id' => $templateid));
}

$content = '';
//le tableau des parametres pour la recherche et pagination
$params = array();
$filter = '';

if (!empty($search)) {
    $param1['paramname'] = "search";
    $param1['paramvalue'] = $search;
    array_push($params, $param1);
    $filter = '&search=' . $search;
}

$templates = $DB->get_records_sql('SELECT * FROM mdl_smartch_mail_templates', null);

$context = context_system::instance();
$PAGE->set_url(new moodle_url('/theme/remui/views/mailtemplates/index.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title("Templates mails");

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

//le header avec bouton de retour au panneau admin
$templatecontextheader = (object)[
    'url' => new moodle_url('/theme/remui/views/adminmenu.php'),
    'textcontent' => 'Retour au panneau d\'administration'
];
$content .= $OUTPUT->render_from_template('theme_remui/smartch_header_back', $templatecontextheader);

$content .= '<div class="row" style="margin:30px 0;"></div>';


//barre de recherche des templates
$templatecontext = (object)[
    'formurl' => new moodle_url('/theme/remui/views/mailtemplates/index.php'),
    'textcontent' => "Tous les templates",
    'lang_search' => "Rechercher",
    'params' => $params,
    'search' => $search
];
$content .= $OUTPUT->render_from_template('theme_remui/smartch_header_search', $templatecontext);


//nouveau template
$content .= '<div class="row">
    <div class="col-sm-12 col-md-12 col-lg-12 col-xl-12">
        <a href="' . new moodle_url('/theme/remui/mailtemplates/create.php') . '" class="smartch_btn">Nouveau template</a>
    </div>
</div>';

//La pagination
if (count($templates) == 0) {
    $paginationtitle .= 'Aucun résultat';
} else if (count($templates) == 1) {
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

if (count($templates) > 0) {
    $content .= $OUTPUT->render_from_template('theme_remui/smartch_header_pagination', $templatecontextpagination);
}

//--------------------------------
//AFFICHAGE DES TEMPLATES


//affichage de la table des templates
$content .= '<div class="row">
        <div class="col-sm-12 col-md-12 col-lg-12 col-xl-12">
            <table class="smartch_table">
                <thead>
                    <tr>
                        <th>Sujet</th>
                        <th>Contenu</th>
                        <th>Date de modification</th>
                        <th>Options</th>
                    </tr>
                </thead>
                <tbody>';
foreach ($templates as $template) {

    $content .= '<tr>
                    <td style="text-transform:capitalize;">
                        <a href="' . new moodle_url('/theme/remui/mailtemplates/edit.php') . '?templateid=' . $template->id . '">' . $template->subject . '</a></span>
                    </td>
                    <td>' . $template->content . '</td>
                    <td>' . userdate($template->datemodified, get_string('strftimedate')) . '</td>
                    <td>
                        <a class="smartch_table_btn" href="' . new moodle_url('/theme/remui/mailtemplates/edit.php') . '?templateid=' . $template->id . '">
                            <svg style="width:20px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                            </svg>
                        </a>
                        <a class="smartch_table_btn ml-2" href="' . new moodle_url('/theme/remui/mailtemplates/index.php') . '?templateid=' . $template->id . '&action=delete">
                            <svg style="width:20px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                            </svg>
                        </a>
                    </td>
                </tr>';
}

$content .= '</tbody>
            </table>
        </div>
    </div>';

//--------------------------------

//la pagination en bas
if (count($templates) > 0) {
    $content .= $OUTPUT->render_from_template('theme_remui/smartch_header_pagination', $templatecontextpagination);
} else {
    $content .= nothingtodisplay("Aucun template trouvé");
}

echo $content;


echo $OUTPUT->footer();

