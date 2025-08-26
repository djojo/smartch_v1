<?php

require_once __DIR__ . '/../../../config.php';
require_once './utils.php';

require_login();

global $USER, $DB, $CFG;

$userid     = optional_param('userid', null, PARAM_INT);
$returnurl  = optional_param('returnurl', $CFG->wwwroot . '/theme/remui/views/adminusers.php', PARAM_URL);
$templateid = optional_param('template', null, PARAM_INT);
$send       = optional_param('send', false, PARAM_BOOL);

// Traitement de l'envoi
if ($send && $templateid && $userid) {
    $template = $DB->get_record('smartch_mailtemplates', ['id' => $templateid]);
    $user     = $DB->get_record('user', ['id' => $userid]);

    if ($template && $user) {
        // Préparer les variables
        $variables = [
            '{{username}}'        => $user->username,
            '{{firstname}}'       => $user->firstname,
            '{{lastname}}'        => $user->lastname,
            '{{email}}'           => $user->email,
            '{{sitename}}'        => $SITE->fullname,
            '{{date}}'            => date('d/m/Y'),
            '{{time}}'            => date('H:i'),
            '{{datetime}}'        => date('d/m/Y à H:i'),
            '{{message}}'         => '',               // Pour message personnalisé,contenu libre
            '{{senderfirstname}}' => $USER->firstname, // Prénom de l'expéditeur 
            '{{senderlastname}}'  => $USER->lastname,  // Nom de l'expéditeur 
            '{{coursename}}'      => '',               // Pas de cours spécifique pour envoi utilisateur
            '{{courselink}}'      => '',               // pas de lien cours
        ];

        // Envoyer l'email
        $result = send_template_email_by_id($user, $template->id, $variables);

        if ($result) {
            $message      = "Message envoyé avec succès à " . $user->firstname . " " . $user->lastname;
            $message_type = 'success';
        } else {
            $message      = "Erreur lors de l'envoi du message";
            $message_type = 'error';
        }
    }
}

$context = context_system::instance();
$PAGE->set_url(new moodle_url('/theme/remui/views/usermessage.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title("Envoyer un message");

echo $OUTPUT->header();

echo '<style>
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
</style>';

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

// Formulaire
$content .= '<div class="message-form">
    <form method="post">
        <div class="form-group">
            <label class="form-label">Template à utiliser :</label>
            <select name="template" class="form-control" required>';

// Récupérer tous les templates
$templates = get_all_templates();
$content .= '<option value="">-- Choisir un template --</option>';
foreach ($templates as $template) {
    $selected = ($templateid == $template->id) ? 'selected' : '';
    $content .= '<option value="' . $template->id . '" ' . $selected . '>' .
    htmlspecialchars($template->name) . ' (' . $template->type . ')</option>';
}

$content .= '</select>
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
