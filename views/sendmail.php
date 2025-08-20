<?php
// Interface d'envoi admin

require_once(__DIR__ . '/../../../config.php');
require_once('./utils.php');

require_login();
isAdminFormation();

global $USER, $DB, $CFG;

// Contrôle d'accès
$rolename = getMainRole();
if (!($rolename == "super-admin" || $rolename == "manager" || $rolename == "smalleditingteacher" || $rolename == "editingteacher")) {
    redirect($CFG->wwwroot);
}

// Contexte 
$context = context_system::instance();
$PAGE->set_url(new moodle_url('/theme/remui/views/sendmail.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title("Envoyer un email");

// Traitement du formulaire
$templateid = optional_param('template', null, PARAM_INT);
$userid = optional_param('user', null, PARAM_INT);
$send = optional_param('send', false, PARAM_BOOL);

if ($send && $templateid && $userid) {
    $template = $DB->get_record('smartch_mailtemplates', ['id' => $templateid]);
    $user = $DB->get_record('user', ['id' => $userid]); // Récupère les objets template et utilisateur
    
    if ($template && $user) {
        $variables = [
            '{{username}}' => $user->username,
            '{{firstname}}' => $user->firstname,
            '{{lastname}}' => $user->lastname,
            '{{email}}' => $user->email
        ];
        
        $result = send_template_email($user, $template->name, $variables); // Appelle la fonction du moteur
        
        if ($result) {
            $message = "Email envoyé avec succès à " . $user->firstname . " " . $user->lastname;
        } else {
            $message = "Erreur lors de l'envoi de l'email";
        }
    }
}

echo $OUTPUT->header();

echo '<style>
    .collapsible-actions { display:none !important; }
    #page.drawers .main-inner {
        margin-top: 150px;
        margin-bottom: 3.5rem;
    }
    div[role=main] { margin-top: 0 !important; }
    
    .email-form {
        background: white;
        padding: 30px;
        border-radius: 15px;
        border: 1px solid #004686;
        max-width: 600px;
        margin: 30px auto;
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
    
    .form-control {
        width: 100%;
        padding: 10px;
        border: 1px solid #ddd;
        border-radius: 5px;
    }
    
    .btn-send {
        background: #004686;
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: 5px;
        cursor: pointer;
        font-weight: bold;
    }
    
    .btn-send:hover {
        background: #003366;
    }
    
    .success-message {
        background: #d4edda;
        color: #155724;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        border: 1px solid #c3e6cb;
    }
    
    .error-message {
        background: #f8d7da;
        color: #721c24;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        border: 1px solid #f5c6cb;
    }
</style>';

$content = "";

//Bouton retour
$content .= '<a href="' . new moodle_url('/theme/remui/views/adminmenu.php') . '" style="font-size:0.8rem;cursor: pointer; display: flex; align-items: center; position: absolute; top: 120px;">
<svg width="8" height="15" viewBox="0 0 6 10" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path fill-rule="evenodd" clip-rule="evenodd" d="M5.70711 0.292893C6.09763 0.683417 6.09763 1.31658 5.70711 1.70711L2.41421 5L5.70711 8.29289C6.09763 8.68342 6.09763 9.31658 5.70711 9.70711C5.31658 10.0976 4.68342 10.0976 4.29289 9.70711L0.292893 5.70711C-0.0976311 5.31658 -0.0976311 4.68342 0.292893 4.29289L4.29289 0.292893C4.68342 -0.0976311 5.31658 -0.0976311 5.70711 0.292893Z" fill="white"/>
</svg>
<div class="ml-4 FFF-White FFF-Equipe-Regular">Retour</div>
</a>';

// Titre
$content .= '<h1 style="margin-bottom:30px;letter-spacing:1px;" class="smartch_title FFF-Hero-Bold FFF-Blue">Envoyer un Email</h1>';

// Message de résultat
if (isset($message)) {
    $class = $result ? 'success-message' : 'error-message';
    $content .= '<div class="' . $class . '">' . $message . '</div>';
}

// Formulaire
$content .= '<div class="email-form">
    <form method="post">
        <div class="form-group">
            <label class="form-label">Template à utiliser :</label>
            <select name="template" class="form-control" required>';

//Récupérer tous les templates 

$templates = get_all_templates();
$content .= '<option value="">-- Choisir un template --</option>';
foreach ($templates as $template) {
    $selected = ($templateid == $template->id) ? 'selected' : '';
    $content .= '<option value="' . $template->id . '" ' . $selected . '>' . 
                htmlspecialchars($template->name) . ' (' . $template->type . ')</option>';
}

$content .= '</select>
        </div>
        
        <div class="form-group">
            <label class="form-label">Utilisateur destinataire :</label>
            <select name="user" class="form-control" required>';

//Récupérer tous les utilisateurs actifs

$users = $DB->get_records_sql('SELECT id, firstname, lastname, email FROM {user} WHERE deleted = 0 AND suspended = 0 ORDER BY firstname ASC LIMIT 50');
$content .= '<option value="">-- Choisir un utilisateur --</option>';
foreach ($users as $user) {
    $selected = ($userid == $user->id) ? 'selected' : '';
    $content .= '<option value="' . $user->id . '" ' . $selected . '>' . 
                htmlspecialchars($user->firstname . ' ' . $user->lastname . ' (' . $user->email . ')') . '</option>';
}

$content .= '</select>
        </div>
        
        <div class="form-group">
            <input type="hidden" name="send" value="1">
            <button type="submit" class="btn-send">Envoyer l\'email</button>
        </div>
    </form>
</div>';

// Lien vers la gestion des templates
$content .= '<div style="text-align: center; margin-top: 30px;">
    <a href="' . new moodle_url('/theme/remui/views/mailtemplates/index.php') . '" style="color: #004686;">
        → Gérer les templates d\'email
    </a>
</div>';

echo $content;

echo $OUTPUT->footer();