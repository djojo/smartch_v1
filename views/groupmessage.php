<?php

require_once __DIR__ . '/../../../config.php';
require_once './utils.php';

require_login();

global $USER, $DB, $CFG, $SITE;

$teamid     = optional_param('teamid', null, PARAM_INT);
$returnurl  = optional_param('returnurl', $CFG->wwwroot . '/theme/remui/views/adminteams.php', PARAM_URL);
$templateid = optional_param('template', null, PARAM_INT);
$send       = optional_param('send', false, PARAM_BOOL);
$mode       = optional_param('mode', 'template', PARAM_TEXT);

// Fonction pour récupérer les utilisateurs d'un groupe
function get_group_users($groupid)
{
    global $DB;
    $sql = 'SELECT DISTINCT u.id, u.firstname, u.lastname, u.email, u.username
            FROM {groups_members} gm
            JOIN {user} u ON u.id = gm.userid
            WHERE gm.groupid = :groupid
            AND u.deleted = 0 AND u.suspended = 0';
    return $DB->get_records_sql($sql, ['groupid' => $groupid]);
}

// Traitement de l'envoi
if ($send && $teamid) {
    $group = $DB->get_record('groups', ['id' => $teamid]);
    $course = $DB->get_record('course', ['id' => $group->courseid]);
    $users = get_group_users($teamid);
    $success_count = 0;
    $total_users = count($users);

    if ($mode === 'template' && $templateid) {
        // MODE TEMPLATE
        $template_subject = optional_param('template_subject', '', PARAM_TEXT);
        $template_content = optional_param('template_content', '', PARAM_RAW);
        
        if ($group && !empty($template_subject) && !empty($template_content)) {
            foreach ($users as $user) {
                $variables = [
                    '{{username}}'        => $user->username,
                    '{{firstname}}'       => $user->firstname,
                    '{{lastname}}'        => $user->lastname,
                    '{{email}}'           => $user->email,
                    '{{sitename}}'        => $SITE->fullname,
                    '{{date}}'            => date('d/m/Y'),
                    '{{time}}'            => date('H:i'),
                    '{{datetime}}'        => date('d/m/Y à H:i'),
                    '{{senderfirstname}}' => $USER->firstname,
                    '{{senderlastname}}'  => $USER->lastname,
                    '{{groupname}}'       => $group->name,
                    '{{coursename}}'      => $course->fullname ?? '',
                    '{{courselink}}'      => $course ? new moodle_url('/course/view.php', ['id' => $course->id]) : '',
                ];

                $final_subject = str_replace(array_keys($variables), array_values($variables), $template_subject);
                $final_content = str_replace(array_keys($variables), array_values($variables), $template_content);
                
                $from = $DB->get_record('user', ['id' => $USER->id]);
                $result = email_to_user($user, $from, $final_subject, $final_content, $final_content);
                if ($result) {
                    $success_count++;
                }
            }
        }
    } elseif ($mode === 'libre') {
        // MODE LIBRE
        $subject = optional_param('subject', '', PARAM_TEXT);
        $content = optional_param('content', '', PARAM_RAW);
        
        if (!empty($subject) && !empty($content)) {
            foreach ($users as $user) {
                $from = $DB->get_record('user', ['id' => $USER->id]);
                $result = email_to_user($user, $from, $subject, $content, $content);
                if ($result) {
                    $success_count++;
                }
            }
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
}

$context = context_system::instance();
$PAGE->set_url(new moodle_url('/theme/remui/views/groupmessage.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title("Nouveau message pour groupe");

echo $OUTPUT->header();
?>

<style>
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

    .mode-selector {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    .mode-option {
        margin-right: 20px;
    }

    .template-fields, .libre-fields {
        display: none;
    }

    .libre-fields textarea {
        min-height: 150px;
    }

    .success-message {
        background: #d4edda;
        color: #155724;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    .warning-message {
        background: #fff3cd;
        color: #856404;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    .error-message {
        background: #f8d7da;
        color: #721c24;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
    }

    .recipient-info {
        background: #f8f9fa;
        padding: 15px;
        border-radius: 5px;
        margin-bottom: 20px;
        border-left: 4px solid #004686;
    }
</style>

<script>
function toggleMode() {
    var mode = document.querySelector("input[name='mode']:checked").value;
    var templateFields = document.querySelector(".template-fields");
    var libreFields = document.querySelector(".libre-fields");
    
    if (mode === "template") {
        templateFields.style.display = "block";
        libreFields.style.display = "none";
    } else {
        templateFields.style.display = "none";
        libreFields.style.display = "block";
    }
}

function loadTemplate() {
    var select = document.querySelector("select[name='template']");
    var selectedOption = select.options[select.selectedIndex];
    
    if (selectedOption.value) {
        document.getElementById("template_subject").value = selectedOption.getAttribute("data-subject");
        
        var content = selectedOption.getAttribute("data-content");
        // Nettoyer le HTML
        content = content.replace(/<p[^>]*>/gi, "");
        content = content.replace(/<\/p>/gi, "\n");
        content = content.replace(/<br[^>]*>/gi, "\n");
        content = content.replace(/<div[^>]*>/gi, "");
        content = content.replace(/<\/div>/gi, "\n");
        content = content.replace(/&nbsp;/gi, " ");
        content = content.replace(/&lt;/gi, "<");
        content = content.replace(/&gt;/gi, ">");
        content = content.replace(/&quot;/gi, '"');
        content = content.replace(/&#039;/gi, "'");
        
        document.getElementById("template_content").value = content;
    } else {
        document.getElementById("template_subject").value = "";
        document.getElementById("template_content").value = "";
    }
}

document.addEventListener("DOMContentLoaded", function() {
    toggleMode();
    loadTemplate();
});
</script>

<?php

$content = "";

// Bouton retour
$content .= '<a href="' . $returnurl . '" style="font-size:0.8rem;cursor: pointer; display: flex; align-items: center; margin-bottom: 20px;">
    ← Retour au groupe
</a>';

// Titre
if ($teamid) {
    $group = $DB->get_record('groups', ['id' => $teamid]);
    $content .= '<h1 style="color: #004686; margin-bottom: 30px;">Nouveau message pour ' . htmlspecialchars($group->name) . '</h1>';
} else {
    $content .= '<h1 style="color: #004686; margin-bottom: 30px;">Nouveau message pour groupe</h1>';
}

// Message de résultat
if (isset($message)) {
    $class = $message_type . '-message';
    $content .= '<div class="' . $class . '">' . $message . '</div>';
}

// Aide sur les variables disponibles
$content .= '<div style="background: #f8f9fa; padding: 20px; border-radius: 10px; border-left: 4px solid #004686; margin-bottom: 30px;">
    <h3 style="color: #004686; margin-bottom: 15px;">Variables disponibles</h3>
    <p style="margin-bottom: 15px;">Utilisez ces variables dans vos messages. Elles seront automatiquement remplacées :</p>
    
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-top: 15px;">
        <div style="background: white; padding: 8px 12px; border-radius: 5px; font-family: monospace; font-size: 13px; border: 1px solid #ddd;">{{username}} - Nom d\'utilisateur</div>
        <div style="background: white; padding: 8px 12px; border-radius: 5px; font-family: monospace; font-size: 13px; border: 1px solid #ddd;">{{firstname}} - Prénom</div>
        <div style="background: white; padding: 8px 12px; border-radius: 5px; font-family: monospace; font-size: 13px; border: 1px solid #ddd;">{{lastname}} - Nom de famille</div>
        <div style="background: white; padding: 8px 12px; border-radius: 5px; font-family: monospace; font-size: 13px; border: 1px solid #ddd;">{{email}} - Email</div>
        <div style="background: white; padding: 8px 12px; border-radius: 5px; font-family: monospace; font-size: 13px; border: 1px solid #ddd;">{{sitename}} - Nom du site</div>
        <div style="background: white; padding: 8px 12px; border-radius: 5px; font-family: monospace; font-size: 13px; border: 1px solid #ddd;">{{groupname}} - Nom du groupe</div>
        <div style="background: white; padding: 8px 12px; border-radius: 5px; font-family: monospace; font-size: 13px; border: 1px solid #ddd;">{{coursename}} - Nom du cours</div>
        <div style="background: white; padding: 8px 12px; border-radius: 5px; font-family: monospace; font-size: 13px; border: 1px solid #ddd;">{{courselink}} - Lien du cours</div>
        <div style="background: white; padding: 8px 12px; border-radius: 5px; font-family: monospace; font-size: 13px; border: 1px solid #ddd;">{{date}} - Date actuelle</div>
        <div style="background: white; padding: 8px 12px; border-radius: 5px; font-family: monospace; font-size: 13px; border: 1px solid #ddd;">{{time}} - Heure actuelle</div>
        <div style="background: white; padding: 8px 12px; border-radius: 5px; font-family: monospace; font-size: 13px; border: 1px solid #ddd;">{{datetime}} - Date et heure</div>
        <div style="background: white; padding: 8px 12px; border-radius: 5px; font-family: monospace; font-size: 13px; border: 1px solid #ddd;">{{senderfirstname}} - Prénom expéditeur</div>
        <div style="background: white; padding: 8px 12px; border-radius: 5px; font-family: monospace; font-size: 13px; border: 1px solid #ddd;">{{senderlastname}} - Nom expéditeur</div>
    </div>
</div>';

// Affichage des informations du groupe
if ($teamid) {
    $group = $DB->get_record('groups', ['id' => $teamid]);
    $users = get_group_users($teamid);
    $course = $DB->get_record('course', ['id' => $group->courseid]);

    if ($group) {
        $content .= '<div class="recipient-info">';
        $content .= '<h4>Destinataires : Groupe</h4>';
        $content .= '<p><strong>Groupe :</strong> ' . htmlspecialchars($group->name) . '</p>';
        if ($course) {
            $content .= '<p><strong>Cours :</strong> ' . htmlspecialchars($course->fullname) . '</p>';
        }
        $content .= '<p><strong>Nombre d\'utilisateurs :</strong> ' . count($users) . '</p>';
        $content .= '</div>';
    }
}

// Formulaire hybride
$content .= '<div class="message-form">
    <form method="post">
        <div class="mode-selector">
            <label class="form-label">Choisir le mode d\'envoi :</label>
            <div class="mode-option">
                <label>
                    <input type="radio" name="mode" value="template" ' . ($mode === 'template' ? 'checked' : '') . ' onchange="toggleMode()">
                    Utiliser un template
                </label>
            </div>
            <div class="mode-option">
                <label>
                    <input type="radio" name="mode" value="libre" ' . ($mode === 'libre' ? 'checked' : '') . ' onchange="toggleMode()">
                    Message libre
                </label>
            </div>
        </div>

        <div class="template-fields">
            <div class="form-group">
                <label class="form-label">Template à utiliser :</label>
                <select name="template" class="form-control" onchange="loadTemplate()">';

$templates = get_all_templates();
$content .= '<option value="">-- Choisir un template --</option>';
foreach ($templates as $template) {
    $selected = ($templateid == $template->id) ? 'selected' : '';
    $content .= '<option value="' . $template->id . '" ' . $selected . 
                ' data-subject="' . htmlspecialchars($template->subject) . '"' .
                ' data-content="' . htmlspecialchars($template->content) . '">' .
                htmlspecialchars($template->name) . ' (' . $template->type . ')</option>';
}

$content .= '</select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Sujet du message :</label>
                <input type="text" name="template_subject" id="template_subject" class="form-control" placeholder="Le sujet sera chargé depuis le template">
            </div>
            <div class="form-group">
                <label class="form-label">Contenu du message :</label>
                <textarea name="template_content" id="template_content" class="form-control" rows="8" placeholder="Le contenu sera chargé depuis le template..."></textarea>
            </div>
        </div>

        <div class="libre-fields">
            <div class="form-group">
                <label class="form-label">Sujet du message :</label>
                <input type="text" name="subject" class="form-control" placeholder="Entrez le sujet de votre message">
            </div>
            <div class="form-group">
                <label class="form-label">Contenu du message :</label>
                <textarea name="content" class="form-control" rows="8" placeholder="Tapez votre message ici..."></textarea>
            </div>
        </div>

        <input type="hidden" name="teamid" value="' . $teamid . '">
        <input type="hidden" name="returnurl" value="' . htmlspecialchars($returnurl) . '">
        <input type="hidden" name="send" value="1">

        <div class="form-group">
            <button type="submit" class="btn-send" onclick="return confirm(\'Êtes-vous sûr de vouloir envoyer ce message à tous les membres du groupe ?\')">
                Envoyer le message au groupe
            </button>
        </div>
    </form>
</div>';

$content .= '<div style="text-align: center; margin-top: 30px;">
    <a href="' . new moodle_url('/theme/remui/views/mailtemplates/index.php') . '" style="color: #004686;">
        → Gérer les templates d\'email
    </a>
</div>';

echo $content;
echo $OUTPUT->footer();
?>