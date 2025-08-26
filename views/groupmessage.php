<?php

require_once __DIR__ . '/../../../config.php';
require_once './utils.php';

require_login();

global $USER, $DB, $CFG;

$teamid     = optional_param('teamid', null, PARAM_INT);
$returnurl  = optional_param('returnurl', $CFG->wwwroot . '/theme/remui/views/adminteams.php', PARAM_URL);
$templateid = optional_param('template', null, PARAM_INT);
$send       = optional_param('send', false, PARAM_BOOL);

/**
 * Récupère les utilisateurs d'un groupe
 */
function get_group_users($groupid)
{
    global $DB;

    $sql = 'SELECT DISTINCT u.id, u.firstname, u.lastname, u.email, u.username
            FROM mdl_groups_members gm
            JOIN mdl_user u ON u.id = gm.userid
            WHERE gm.groupid = :groupid
            AND u.deleted = 0 AND u.suspended = 0';

    return $DB->get_records_sql($sql, ['groupid' => $groupid]);
}

// Traitement de l'envoi
if ($send && $templateid && $teamid) {
    $template = $DB->get_record('smartch_mailtemplates', ['id' => $templateid]);
    $group    = $DB->get_record('groups', ['id' => $teamid]);
    $course   = $DB->get_record('course', ['id' => $group->courseid]);
    $session  = $DB->get_record('smartch_session', ['groupid' => $teamid]);

    if ($template && $group) {
        $users         = get_group_users($teamid);
        $success_count = 0;
        $total_users   = count($users);

        foreach ($users as $user) {
            // Variables pour chaque utilisateur du groupe
            $variables = [
                '{{username}}'        => $user->username,
                '{{firstname}}'       => $user->firstname,
                '{{lastname}}'        => $user->lastname,
                '{{email}}'           => $user->email,
                '{{sitename}}'        => $SITE->fullname,
                '{{date}}'            => date('d/m/Y'),
                '{{time}}'            => date('H:i'),
                '{{datetime}}'        => date('d/m/Y à H:i'),
                '{{message}}'         => '', // Pour contenu libre
                '{{senderfirstname}}' => $USER->firstname,
                '{{senderlastname}}'  => $USER->lastname,
                '{{groupname}}'       => $group->name,
                '{{coursename}}'      => $course->fullname ?? '',                                                  // NOM DU COURS
                '{{courselink}}'      => $course ? new moodle_url('/course/view.php', ['id' => $course->id]) : '', // LIEN VERS LE COURS
            ];
            // Variables session si disponible
            if ($session) {
                $variables['{{sessionstart}}'] = date('d/m/Y', $session->startdate);
                $variables['{{sessionend}}']   = date('d/m/Y', $session->enddate);
                $variables['{{sessiondates}}'] = date('d/m/Y', $session->startdate) . ' au ' . date('d/m/Y', $session->enddate);
            }

            // Envoyer l'email
            $result = send_template_email_by_id($user, $template->id, $variables);

            if ($result) {
                $success_count++;
            }
        }

        // Message de résultat
        if ($success_count == $total_users) {
            $message      = "Message envoyé avec succès à $success_count utilisateur(s)";
            $message_type = 'success';
        } else {
            $message      = "Message envoyé à $success_count/$total_users utilisateur(s)";
            $message_type = 'warning';
        }
    } else {
        $message      = "Erreur : template ou groupe non trouvé";
        $message_type = 'error';
    }
}

$context = context_system::instance();
$PAGE->set_url(new moodle_url('/theme/remui/views/groupmessage.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title("Nouveau message pour groupe");

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
</style>';

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

// Affichage des informations du groupe
if ($teamid) {
    $group  = $DB->get_record('groups', ['id' => $teamid]);
    $users  = get_group_users($teamid);
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

// Lien vers les templates
$content .= '<div style="text-align: center; margin-top: 30px;">
    <a href="' . new moodle_url('/theme/remui/views/mailtemplates/index.php') . '" style="color: #004686;">
        → Gérer les templates d\'email
    </a>
</div>';

echo $content;
echo $OUTPUT->footer();
