<?php
// Configuration et requirements
require_once(__DIR__ . '/../../../../config.php');
require_once('../utils.php');
require_login();

global $USER, $DB, $CFG;

// Paramètres de la page
$search = optional_param('search', '', PARAM_TEXT);
$pageno = optional_param('pageno', 1, PARAM_INT);
$action = optional_param('action', null, PARAM_ALPHA);
$templateid = optional_param('templateid', null, PARAM_INT);

// Gestion de la suppression
if ($action == 'delete' && !empty($templateid)) {
	require_sesskey(); // Sécurité CSRF
	$DB->delete_records('smartch_mail_templates', array('id' => $templateid));
	redirect(
		new moodle_url('/theme/remui/views/mailtemplates/index.php'),
		'Template supprimé avec succès',
		null,
		\core\output\notification::NOTIFY_SUCCESS
	);
}

// Configuration de la page
$context = context_system::instance();
$PAGE->set_url(new moodle_url('/theme/remui/views/mailtemplates/index.php'));
$PAGE->set_context($context);
$PAGE->set_title("Templates mails");
$PAGE->set_heading("Gestion des templates mail");

// Paramètres de recherche et filtres
$params = array();
$sqlwhere = '';
$sqlparams = array();

if (!empty($search)) {
	$sqlwhere = 'WHERE (name LIKE ? OR subject LIKE ? OR category LIKE ?)';
	$searchparam = '%' . $search . '%';
	$sqlparams = array($searchparam, $searchparam, $searchparam);

	$params[] = array(
		'paramname' => 'search',
		'paramvalue' => $search
	);
}

// Pagination
$perpage = 10;
$offset = ($pageno - 1) * $perpage;

// Récupération des données avec pagination
$countsql = "SELECT COUNT(*) FROM {smartch_mail_templates} $sqlwhere";
$total_rows = $DB->count_records_sql($countsql, $sqlparams);
$total_pages = ceil($total_rows / $perpage);

$sql = "SELECT * FROM {smartch_mail_templates} $sqlwhere ORDER BY createdAt DESC";
$templates = $DB->get_records_sql($sql, $sqlparams, $offset, $perpage);

// CSS optimisé
echo '<style>
#page {
    background: transparent !important;
}

#topofscroll {
    background: transparent !important;
    margin-top: 0px !important;
}

@media screen and (max-width: 830px) {
    #topofscroll {
        margin-top: 40px !important;
    }
}

.smartch_table {
    width: 100%;
    border-collapse: collapse;
    margin: 20px 0;
}

.smartch_table th,
.smartch_table td {
    padding: 12px 8px;
    text-align: left;
    border-bottom: 1px solid #ddd;
    vertical-align: top;
}

.smartch_table th {
    background-color: #004687;
    font-weight: bold;
    position: sticky;
    top: 0;
}

.smartch_table tr:hover {
    background-color: #f5f5f5;
}

.smartch_table_btn {
    display: inline-block;
    padding: 5px;
    margin: 0 2px;
    border-radius: 4px;
    transition: background-color 0.3s;
}

.smartch_table_btn:hover {
    background-color: #e9ecef;
}

.content-preview {
    max-width: 200px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.status-badge {
    padding: 4px 8px;
    border-radius: 12px;
    font-size: 12px;
    font-weight: bold;
}

.status-active {
    background-color: #d4edda;
    color: #155724;
}

.status-inactive {
    background-color: #f8d7da;
    color: #721c24;
}
</style>';

echo $OUTPUT->header();

$content = '';

// Header avec bouton de retour
$templatecontextheader = (object)[
	'url' => new moodle_url('/theme/remui/views/adminmenu.php'),
	'textcontent' => 'Retour au panneau d\'administration'
];
$content .= $OUTPUT->render_from_template('theme_remui/smartch_header_back', $templatecontextheader);

// Espace
$content .= '<div class="row" style="margin:30px 0;"></div>';

// Barre de recherche
$templatecontext = (object)[
	'formurl' => new moodle_url('/theme/remui/views/mailtemplates/index.php'),
	'textcontent' => "Tous les templates mails",
	'lang_search' => "Rechercher",
	'params' => $params,
	'search' => $search
];
$content .= $OUTPUT->render_from_template('theme_remui/smartch_header_search', $templatecontext);

// Titre de pagination
if ($total_rows == 0) {
	$paginationtitle = 'Aucun résultat';
} else if ($total_rows == 1) {
	$paginationtitle = '1 résultat';
} else {
	$paginationtitle = $total_rows . ' résultats - page ' . $pageno . ' sur ' . $total_pages;
}

// URLs de pagination
$filter = !empty($search) ? '&search=' . urlencode($search) : '';
$previous = $pageno > 1;
$next = $pageno < $total_pages;
$prevurl = $previous ? new moodle_url('/theme/remui/views/mailtemplates/index.php', ['pageno' => $pageno - 1]) . $filter : '';
$nexturl = $next ? new moodle_url('/theme/remui/views/mailtemplates/index.php', ['pageno' => $pageno + 1]) . $filter : '';

$paginationarray = range(1, $total_pages);

// Contexte de pagination
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
	'formurl' => new moodle_url('/theme/remui/views/mailtemplates/index.php')
];

// Pagination en haut
if ($total_rows > 0) {
	$content .= $OUTPUT->render_from_template('theme_remui/smartch_header_pagination', $templatecontextpagination);
}

// Bouton d'ajout
$content .= '<div class="row mb-3">
    <div class="col-12">
        <a href="' . new moodle_url('/theme/remui/views/mailtemplates/add.php') . '" class="btn btn-primary">
            <i class="fa fa-plus"></i> Ajouter un template
        </a>
    </div>
</div>';

// Table des templates
if ($total_rows > 0) {
	$content .= '<div class="row">
        <div class="col-12">
            <div class="table-responsive">
                <table class="smartch_table table table-striped">
                    <thead>
                        <tr>
                            <th style="width: 15%;">Nom</th>
                            <th style="width: 20%;">Sujet du message</th>
                            <th style="width: 25%;">Contenu</th>
                            <th style="width: 12%;">Catégorie</th>
                            <th style="width: 8%;">Actif</th>
                            <th style="width: 12%;">Créé le</th>
                            <th style="width: 8%;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>';

	foreach ($templates as $template) {
		// Préparation du contenu tronqué
		$contentPreview = strlen($template->content) > 100 ?
			substr(strip_tags($template->content), 0, 100) . '...' :
			strip_tags($template->content);

		// URL de modification
		$editurl = new moodle_url('/theme/remui/views/mailtemplates/edit.php', ['templateid' => $template->id]);

		// URL de suppression avec sesskey
		$deleteurl = new moodle_url('/theme/remui/views/mailtemplates/index.php', [
			'templateid' => $template->id,
			'action' => 'delete',
			'sesskey' => sesskey()
		]);

		$content .= '<tr>
            <td><strong>' . format_string($template->name) . '</strong></td>
            <td>
                <a href="' . $editurl . '" title="Modifier ce template">
                    ' . format_string($template->subject) . '
                </a>
            </td>
            <td>
                <div class="content-preview" title="' . format_string(strip_tags($template->content)) . '">
                    ' . format_string($contentPreview) . '
                </div>
            </td>
            <td><span class="badge badge-secondary">' . format_string($template->category) . '</span></td>
            <td>
                <span class="status-badge ' . ($template->active == 1 ? 'status-active' : 'status-inactive') . '">
                    ' . ($template->active == 1 ? 'Actif' : 'Inactif') . '
                </span>
            </td>
            <td>' . userdate($template->createdAt, get_string('strftimedatefullshort')) . '</td>
            <td>
                <div class="btn-group" role="group">
                    <a class="smartch_table_btn btn btn-sm btn-outline-primary" 
                       href="' . $editurl . '" 
                       title="Modifier">
                        <svg style="width:16px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                        </svg>
                    </a>
                    <a class="smartch_table_btn btn btn-sm btn-outline-danger" 
                       href="' . $deleteurl . '" 
                       title="Supprimer"
                       onclick="return confirm(\'Êtes-vous sûr de vouloir supprimer ce template ?\');">
                        <svg style="width:16px;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                        </svg>
                    </a>
                </div>
            </td>
        </tr>';
	}

	$content .= '</tbody>
                </table>
            </div>
        </div>
    </div>';

	// Pagination en bas
	$content .= $OUTPUT->render_from_template('theme_remui/smartch_header_pagination', $templatecontextpagination);
} else {
	$content .= '<div class="alert alert-info">
        <h4>Aucun template trouvé</h4>
        <p>Il n\'y a actuellement aucun template mail correspondant à vos critères de recherche.</p>
        <a href="' . new moodle_url('/theme/remui/views/mailtemplates/add.php') . '" class="btn btn-primary">
            Créer le premier template
        </a>
    </div>';
}

echo $content;
echo $OUTPUT->footer();

//http://portailformation:8888/theme/remui/views/mailtemplates/index.php
?>
