<?php
// Interface d'envoi de messages

require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/../utils.php');

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
$PAGE->set_url(new moodle_url('/theme/remui/views/messages/create.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title("Envoyer un message");

// Récupération des paramètres
$templateid = optional_param('template', null, PARAM_INT);
$userid = optional_param('userid', null, PARAM_INT);
$groupid = optional_param('groupid', null, PARAM_INT);
$returnurl = optional_param('returnurl', $CFG->wwwroot . '/theme/remui/views/adminmenu.php', PARAM_URL);
$send = optional_param('send', false, PARAM_BOOL);

// Traitement du formulaire
if ($send && $templateid) {
    $template = $DB->get_record('smartch_mailtemplates', ['id' => $templateid]);
    
    if ($template) {
        $success_count = 0;
        $total_users = 0;
        
        // Déterminer les utilisateurs destinataires
        if ($groupid) {
            // Envoi à un groupe d'utilisateurs
            $users = get_group_users($groupid);
            $group = $DB->get_record('groups', ['id' => $groupid]);
            $course = $DB->get_record('course', ['id' => $group->courseid]);
            $session = $DB->get_record('smartch_session', ['groupid' => $groupid]);
        } else if ($userid) {
            // Envoi à un seul utilisateur
            $user = $DB->get_record('user', ['id' => $userid]);
            $users = $user ? [$user] : [];
            $group = null;
            $course = null;
            $session = null;
        }
        
        if (!empty($users)) {
            foreach ($users as $user) {
                $total_users++;
                
                // Préparer les variables pour le template
                $variables = prepare_template_variables($user, $group, $course, $session);
                
                // Envoyer l'email
                $result = send_template_email($user, $template->name, $variables);
                
                if ($result) {
                    $success_count++;
                }
            }
            
            // Message de résultat
            if ($success_count == $total_users) {
                $message = "Message envoyé avec succès à $success_count utilisateur(s)";
                $message_type = 'success';
            } else {
                $message = "Message envoyé à $success_count/$total_users utilisateur(s)";
                $message_type = 'warning';
            }
        } else {
            $message = "Aucun utilisateur trouvé";
            $message_type = 'error';
        }
    } else {
        $message = "Template non trouvé";
        $message_type = 'error';
    }
}

/**
 * Récupère les utilisateurs d'un groupe
 */
function get_group_users($groupid) {
    global $DB;
    
    $sql = 'SELECT DISTINCT u.id, u.firstname, u.lastname, u.email, u.username
            FROM mdl_groups_members gm
            JOIN mdl_user u ON u.id = gm.userid
            WHERE gm.groupid = :groupid
            AND u.deleted = 0 AND u.suspended = 0';
    
    return $DB->get_records_sql($sql, ['groupid' => $groupid]);
}

/**
 * Prépare les variables pour remplacer dans le template
 */
function prepare_template_variables($user, $group = null, $course = null, $session = null) {
    global $SITE;
    
    $variables = [
        '{{username}}' => $user->username,
        '{{firstname}}' => $user->firstname,
        '{{lastname}}' => $user->lastname,
        '{{email}}' => $user->email,
        '{{sitename}}' => $SITE->fullname,
        '{{date}}' => date('d/m/Y'),
        '{{time}}' => date('H:i')
    ];
    
    // Variables liées au cours
    if ($course) {
        $variables['{{coursename}}'] = $course->fullname;
        $variables['{{courselink}}'] = new moodle_url('/course/view.php', ['id' => $course->id]);
    }
    
    // Variables liées au groupe
    if ($group) {
        $variables['{{groupname}}'] = $group->name;
    }
    
    // Variables liées à la session
    if ($session) {
        $variables['{{sessionstart}}'] = date('d/m/Y', $session->startdate);
        $variables['{{sessionend}}'] = date('d/m/Y', $session->enddate);
        $variables['{{sessiondates}}'] = date('d/m/Y', $session->startdate) . ' au ' . date('d/m/Y', $session->enddate);
    }
    
    return $variables;
}

echo $OUTPUT->header();

echo '<style>
    .collapsible-actions { display:none !important; }
    #page.drawers .main-inner {
        margin-top: 150px;
        margin-bottom: 3.5rem;
    }
    div[role=main] { margin-top: 0 !important; }
    
    .message-form {
        background: white;
        padding: 30px;
        border-radius: 15px;
        border: 1px solid #004686;
        max-width: 800px;
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
    
    .warning-message {
        background: #fff3cd;
        color: #856404;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        border: 1px solid #ffeaa7;
    }
    
    .error-message {
        background: #f8d7da;
        color: #721c24;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        border: 1px solid #f5c6cb;
    }
    
    .recipient-info {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        border-left: 4px solid #004686;
    }
    
    .template-preview {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-top: 10px;
        border: 1px solid #ddd;
        max-height: 200px;
        overflow-y: auto;
    }
</style>';

$content = "";

// Bouton retour
$content .= '<a href="' . $returnurl . '" style="font-size:0.8rem;cursor: pointer; display: flex; align-items: center; position: absolute; top: 120px;">
<svg width="8" height="15" viewBox="0 0 6 10" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path fill-rule="evenodd" clip-rule="evenodd" d="M5.70711 0.292893C6.09763 0.683417 6.09763 1.31658 5.70711 1.70711L2.41421 5L5.70711 8.29289C6.09763 8.68342 6.09763 9.31658 5.70711 9.70711C5.31658 10.0976 4.68342 10.0976 4.29289 9.70711L0.292893 5.70711C-0.0976311 5.31658 -0.0976311 4.68342 0.292893 4.29289L4.29289 0.292893C4.68342 -0.0976311 5.31658 -0.0976311 5.70711 0.292893Z" fill="white"/>
</svg>
<div class="ml-4 FFF-White FFF-Equipe-Regular">Retour</div>
</a>';

// Titre
$content .= '<h1 style="margin-bottom:30px;letter-spacing:1px;" class="smartch_title FFF-Hero-Bold FFF-Blue">Envoyer un Message</h1>';

// Message de résultat
if (isset($message)) {
    $class = $message_type . '-message';
    $content .= '<div class="' . $class . '">' . $message . '</div>';
}

// Affichage des informations sur les destinataires
if ($groupid || $userid) {
    $content .= '<div class="recipient-info">';
    
    if ($groupid) {
        $group = $DB->get_record('groups', ['id' => $groupid]);
        $users = get_group_users($groupid);
        $course = $DB->get_record('course', ['id' => $group->courseid]);
        
        $content .= '<h4>Destinataires : Groupe</h4>';
        $content .= '<p><strong>Groupe :</strong> ' . htmlspecialchars($group->name) . '</p>';
        $content .= '<p><strong>Cours :</strong> ' . htmlspecialchars($course->fullname) . '</p>';
        $content .= '<p><strong>Nombre d\'utilisateurs :</strong> ' . count($users) . '</p>';
        
    } else if ($userid) {
        $user = $DB->get_record('user', ['id' => $userid]);
        if ($user) {
            $content .= '<h4>Destinataire : Utilisateur unique</h4>';
            $content .= '<p><strong>Nom :</strong> ' . htmlspecialchars($user->firstname . ' ' . $user->lastname) . '</p>';
            $content .= '<p><strong>Email :</strong> ' . htmlspecialchars($user->email) . '</p>';
        }
    }
    
    $content .= '</div>';
}

// Formulaire
$content .= '<div class="message-form">
    <form method="post" id="messageForm">
        <div class="form-group">
            <label class="form-label">Template à utiliser :</label>
            <select name="template" id="templateSelect" class="form-control" required onchange="previewTemplate()">
                <option value="">-- Choisir un template --</option>';

// Récupérer tous les templates
$templates = get_all_templates();
foreach ($templates as $template) {
    $selected = ($templateid == $template->id) ? 'selected' : '';
    $content .= '<option value="' . $template->id . '" ' . $selected . 
                ' data-subject="' . htmlspecialchars($template->subject) . '"' .
                ' data-content="' . htmlspecialchars($template->content) . '">' . 
                htmlspecialchars($template->name) . ' (' . $template->type . ')</option>';
}

$content .= '</select>
            <div id="templatePreview" class="template-preview" style="display:none;">
                <h5>Aperçu du template :</h5>
                <div><strong>Sujet :</strong> <span id="previewSubject"></span></div>
                <div><strong>Contenu :</strong></div>
                <div id="previewContent"></div>
            </div>
        </div>';

// Champs cachés pour conserver les paramètres
if ($userid) {
    $content .= '<input type="hidden" name="userid" value="' . $userid . '">';
}
if ($groupid) {
    $content .= '<input type="hidden" name="groupid" value="' . $groupid . '">';
}
$content .= '<input type="hidden" name="returnurl" value="' . htmlspecialchars($returnurl) . '">
            <input type="hidden" name="send" value="1">

        <div class="form-group">
            <button type="submit" class="btn-send" onclick="return confirm(\'Êtes-vous sûr de vouloir envoyer ce message ?\')">
                Envoyer le message
            </button>
        </div>
    </form>
</div>';

// JavaScript pour l'aperçu du template
$content .= '<script>
function previewTemplate() {
    const select = document.getElementById("templateSelect");
    const preview = document.getElementById("templatePreview");
    const previewSubject = document.getElementById("previewSubject");
    const previewContent = document.getElementById("previewContent");
    
    if (select.value) {
        const option = select.options[select.selectedIndex];
        const subject = option.getAttribute("data-subject");
        const content = option.getAttribute("data-content");
        
        previewSubject.textContent = subject;
        previewContent.innerHTML = content;
        preview.style.display = "block";
    } else {
        preview.style.display = "none";
    }
}

// Afficher l\'aperçu si un template est déjà sélectionné
document.addEventListener("DOMContentLoaded", function() {
    previewTemplate();
});
</script>';

// Lien vers la gestion des templates
$content .= '<div style="text-align: center; margin-top: 30px;">
    <a href="' . new moodle_url('/theme/remui/views/mailtemplates/index.php') . '" style="color: #004686;">
        → Gérer les templates d\'email
    </a>
</div>';

echo $content;

echo $OUTPUT->footer();