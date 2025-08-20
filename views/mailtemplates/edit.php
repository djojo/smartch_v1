<?php
//Création/édition

require_once(__DIR__ . '/../../../../config.php');
require_once('../utils.php');
require_once('./classes/form/templateedit.php');

require_login();
isAdminFormation();

global $USER, $DB, $CFG;

// Contrôle d'accès par rôles
$rolename = getMainRole();
if (!($rolename == "super-admin" || $rolename == "manager" || $rolename == "smalleditingteacher" || $rolename == "editingteacher")) {
    redirect($CFG->wwwroot);
}

$content = "";
$templateid = optional_param('id', null, PARAM_INT);// optional_param() = fonction Moodle pour récupérer paramètres GET/POST de façon sécurisée

// Si on édite un template existant
$template = new stdClass(); // Objet PHP standard vide
if ($templateid) {
    $template = $DB->get_record('smartch_mailtemplates', ['id' => $templateid]); // get_record() = récupère UN enregistrement de la BDD
    if (!$template) {
        redirect($CFG->wwwroot . '/theme/remui/views/mailtemplates/index.php');
    }
}

$context = context_system::instance();
$PAGE->set_url(new moodle_url('/theme/remui/views/mailtemplates/edit.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title($templateid ? "Éditer le template" : "Créer un template");

// Prépare les données à passer au formulaire
$to_form = array(
    'id' => $templateid,
    'name' => isset($template->name) ? $template->name : '',
    'subject' => isset($template->subject) ? $template->subject : '',
    'content' => isset($template->content) ? $template->content : '',
    'type' => isset($template->type) ? $template->type : 'general'
	 
);

$mform = new templateedit(null, $to_form);//Instancie le formulaire avec les données

// Traitement du formulaire
if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot . '/theme/remui/views/mailtemplates/index.php');
} else if ($fromform = $mform->get_data()) {
	// Récupération des données du formulaire
    $templatedata = new stdClass();
    $templatedata->name = $fromform->name;
    $templatedata->subject = $fromform->subject;
    $templatedata->content = $fromform->content['text'];// ['text'] car c'est un éditeur
    $templatedata->type = $fromform->type;
    $templatedata->timemodified = time();// Timestamp Unix
    
    if ($templateid) {
        // Mise à jour
        $templatedata->id = $templateid;
        $DB->update_record('smartch_mailtemplates', $templatedata);
    } else {
        // Création
        $templatedata->timecreated = time();
        $DB->insert_record('smartch_mailtemplates', $templatedata);
    }
    
    redirect($CFG->wwwroot . '/theme/remui/views/mailtemplates/index.php');
}

echo $OUTPUT->header();

// CSS personnalisé
echo '<style>
    .collapsible-actions { display:none !important; }
    #page.drawers .main-inner {
        margin-top: 150px;
        margin-bottom: 3.5rem;
    }
    div[role=main] { margin-top: 0 !important; }
    
    .template-form {
        background: white;
        padding: 30px;
        border-radius: 15px;
        border: 1px solid #004686;
        max-width: 800px;
        margin: 0 auto;
    }
    
    .form-group {
        margin-bottom: 20px;
    }
    
    .form-label {
        color: #004686;
        font-weight: bold;
        margin-bottom: 5px;
        display: block;
    }
    
    .variables-help {
        background: #f8f9fa;
        padding: 20px;
        border-radius: 10px;
        border-left: 4px solid #004686;
        margin-bottom: 30px;
    }
    
    .variable-list {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
        gap: 10px;
        margin-top: 15px;
    }
    
    .variable-item {
        background: white;
        padding: 8px 12px;
        border-radius: 5px;
        font-family: monospace;
        font-size: 13px;
        border: 1px solid #ddd;
    }
</style>';

// Bouton retour
$content .= '<a href="' . new moodle_url('/theme/remui/views/mailtemplates/index.php') . '" style="font-size:0.8rem;cursor: pointer; display: flex; align-items: center; position: absolute; top: 120px;">
<svg width="8" height="15" viewBox="0 0 6 10" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path fill-rule="evenodd" clip-rule="evenodd" d="M5.70711 0.292893C6.09763 0.683417 6.09763 1.31658 5.70711 1.70711L2.41421 5L5.70711 8.29289C6.09763 8.68342 6.09763 9.31658 5.70711 9.70711C5.31658 10.0976 4.68342 10.0976 4.29289 9.70711L0.292893 5.70711C-0.0976311 5.31658 -0.0976311 4.68342 0.292893 4.29289L4.29289 0.292893C4.68342 -0.0976311 5.31658 -0.0976311 5.70711 0.292893Z" fill="white"/>
</svg>
<div class="ml-4 FFF-White FFF-Equipe-Regular">Retour</div>
</a>';

// Titre
$content .= '<h1 style="margin-bottom:30px;letter-spacing:1px;" class="smartch_title FFF-Hero-Bold FFF-Blue">' . 
    ($templateid ? 'Éditer le template' : 'Créer un template') . '</h1>';
// Aide sur les variables
$content .= '<div class="variables-help">
    <h3 style="color: #004686; margin-bottom: 15px;">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="vertical-align: middle; margin-right: 8px;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        Variables disponibles
    </h3>
    <p style="margin-bottom: 15px;">Utilisez ces variables dans vos templates. Elles seront automatiquement remplacées par les valeurs appropriées:</p>
	<!-- Variables disponibles -->
    <div class="variable-list">
        <div class="variable-item">{{username}} - Nom d\'utilisateur</div>
        <div class="variable-item">{{firstname}} - Prénom</div>
        <div class="variable-item">{{lastname}} - Nom de famille</div>
        <div class="variable-item">{{email}} - Email</div>
        <div class="variable-item">{{sitename}} - Nom du site</div>
        <div class="variable-item">{{courselink}} - Lien du cours</div>
        <div class="variable-item">{{coursename}} - Nom du cours</div>
        <div class="variable-item">{{date}} - Date actuelle</div>
    </div>
</div>';

echo $content;

// Affichage du formulaire
$mform->display();

echo $OUTPUT->footer();

//url: http://portailformation:8888/theme/remui/views/mailtemplates/edit.php