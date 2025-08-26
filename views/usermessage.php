<?php

require_once __DIR__ . '/../../../config.php';
require_once './utils.php';

require_login();

global $USER, $DB, $CFG, $SITE;

$userid     = optional_param('userid', null, PARAM_INT);
$returnurl  = optional_param('returnurl', $CFG->wwwroot . '/theme/remui/views/adminusers.php', PARAM_URL);
$templateid = optional_param('template', null, PARAM_INT);
$send       = optional_param('send', false, PARAM_BOOL);
$mode       = optional_param('mode', 'template', PARAM_TEXT);

// Traitement de l'envoi
if ($send && $userid) {
    $user = $DB->get_record('user', ['id' => $userid]);
    $success = false;

    if ($mode === 'template' && $templateid) {
        // MODE TEMPLATE
        $template_subject = optional_param('template_subject', '', PARAM_TEXT);
        $template_content = optional_param('template_content', '', PARAM_RAW);
        
        if ($user && !empty($template_subject) && !empty($template_content)) {
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
                '{{coursename}}'      => '',
                '{{courselink}}'      => '',
            ];

            $final_subject = str_replace(array_keys($variables), array_values($variables), $template_subject);
            $final_content = str_replace(array_keys($variables), array_values($variables), $template_content);
            
            $from = $DB->get_record('user', ['id' => $USER->id]);
            $success = email_to_user($user, $from, $final_subject, $final_content, $final_content);
        }
    } elseif ($mode === 'libre') {
        // MODE LIBRE
        $subject = optional_param('subject', '', PARAM_TEXT);
        $content = optional_param('content', '', PARAM_RAW);
        
        if ($user && !empty($subject) && !empty($content)) {
            $from = $DB->get_record('user', ['id' => $USER->id]);
            $success = email_to_user($user, $from, $subject, $content, $content);
        }
    }

    // Message de résultat
    if ($success) {
        $message = "Message envoyé avec succès à " . $user->firstname . " " . $user->lastname;
        $message_type = 'success';
    } else {
        $message = "Erreur lors de l'envoi du message";
        $message_type = 'error';
    }
}

$context = context_system::instance();
$PAGE->set_url(new moodle_url('/theme/remui/views/usermessage.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title("Envoyer un message");

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
    ← Retour
</a>';

// Titre
$content .= '<h1 style="color: #004686; margin-bottom: 30px;">Nouveau message</h1>';

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
        <div style="background: white; padding: 8px 12px; border-radius: 5px; font-family: monospace; font-size: 13px; border: 1px solid #ddd;">{{date}} - Date actuelle</div>
        <div style="background: white; padding: 8px 12px; border-radius: 5px; font-family: monospace; font-size: 13px; border: 1px solid #ddd;">{{time}} - Heure actuelle</div>
        <div style="background: white; padding: 8px 12px; border-radius: 5px; font-family: monospace; font-size: 13px; border: 1px solid #ddd;">{{datetime}} - Date et heure</div>
        <div style="background: white; padding: 8px 12px; border-radius: 5px; font-family: monospace; font-size: 13px; border: 1px solid #ddd;">{{senderfirstname}} - Prénom expéditeur</div>
        <div style="background: white; padding: 8px 12px; border-radius: 5px; font-family: monospace; font-size: 13px; border: 1px solid #ddd;">{{senderlastname}} - Nom expéditeur</div>
    </div>
</div>';

// Affichage des informations utilisateur
if ($userid) {
    $user = $DB->get_record('user', ['id' => $userid]);
    if ($user) {
        $content .= '<div class="recipient-info">';
        $content .= '<h4>Destinataire</h4>';
        $content .= '<p><strong>Nom :</strong> ' . htmlspecialchars($user->firstname . ' ' . $user->lastname) . '</p>';
        $content .= '<p><strong>Email :</strong> ' . htmlspecialchars($user->email) . '</p>';
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

        <input type="hidden" name="userid" value="' . $userid . '">
        <input type="hidden" name="returnurl" value="' . htmlspecialchars($returnurl) . '">
        <input type="hidden" name="send" value="1">

        <div class="form-group">
            <button type="submit" class="btn-send">Envoyer le message</button>
        </div>
    </form>
</div>';

echo $content;
echo $OUTPUT->footer();
?>