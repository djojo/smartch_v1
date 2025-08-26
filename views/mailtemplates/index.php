<?php

//Liste des templates

// Chemin vers le fichier de configuration Moodle
require_once __DIR__ . '/../../../../config.php'; // config.php = fichier principal Moodle (chemin relatif depuis le dossier actuel)
// Chemin vers les fichiers utilitaires
require_once '../utils.php';

// Vérification de l'authentification, Fonction Moodle : vérifier que l'utilisateur est connecté
require_login();
// Vérification des autorisations, Fonction personnalisée : vérifier les droits admin
isAdminFormation();

// Vérification des autorisations
global $USER, $DB, $CFG;

// Contrôle d'accès par rôles
$rolename = getMainRole(); // Fonction personnalisée
if (! ($rolename == "super-admin" || $rolename == "manager" || $rolename == "smalleditingteacher" || $rolename == "editingteacher")) {
    redirect($CFG->wwwroot); // Redirection vers l'accueil si pas autorisé
}

// Fonction pour traduire les types de templates
function get_template_type_label($type)
{
    $types = [
        'general'      => 'Général',
        'inscription'  => 'Inscription',
        'validation'   => 'Validation',
        'rappel'       => 'Rappel',
        'completion'   => 'Fin de formation',
        'welcome'      => 'Bienvenue',
        'notification' => 'Notification',
    ];

    return isset($types[$type]) ? $types[$type] : ucfirst($type);
}

// Gestion des actions (suppression)
$templateid = optional_param('delete', null, PARAM_INT); // optional_param() = fonction Moodle pour récupérer paramètres GET/POST de façon sécurisée
if ($templateid) {
    $DB->delete_records('smartch_mailtemplates', ['id' => $templateid]); //delete_records() = fonction Moodle pour supprimer des enregistrements
    redirect($CFG->wwwroot . '/theme/remui/views/mailtemplates/index.php');
}

// Contexte
$context = context_system::instance();
// $PAGE = objet global Moodle pour configurer la page web
$PAGE->set_url(new moodle_url('/theme/remui/views/mailtemplates/index.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title("Gestion des templates d'email");

echo $OUTPUT->header();

// CSS personnalisé
echo '<style>
    .collapsible-actions { display:none !important; }
    #page.drawers .main-inner {
        margin-top: 150px;
        margin-bottom: 3.5rem;
    }
    div[role=main] { margin-top: 0 !important; }

    .template-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-top: 30px;
    }

    .template-card {
        background: white;
        border-radius: 15px;
        border: 1px solid #004686;
        padding: 20px;
        position: relative;
        transition: transform 0.3s ease;
        min-height: 200px;
    }

    .template-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 5px 15px rgba(0,70,134,0.2);
    }

    .template-type-badge {
        position: absolute;
        top: 15px;
        left: 15px;
        background: #004686;
        color: white;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: bold;
        text-transform: uppercase;
    }

    .template-actions {
        position: absolute;
        top: 15px;
        right: 15px;
        display: flex;
        gap: 10px;
    }

    .btn-action {
        background: none;
        border: none;
        cursor: pointer;
        padding: 5px;
        border-radius: 50%;
        transition: background 0.3s ease;
    }

    .btn-action:hover {
        background: rgba(0,70,134,0.1);
    }

    .btn-create {
        background: #004686;
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: 8px;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 10px;
        font-weight: bold;
        transition: background 0.3s ease;
    }

    .btn-create:hover {
        background: #003366;
        color: white;
        text-decoration: none;
    }
</style>';

$content = "";

// Bouton retour
$content .= '<a href="' . new moodle_url('/theme/remui/views/adminmenu.php') . '" style="font-size:0.8rem;cursor: pointer; display: flex; align-items: center; position: absolute; top: 120px;">
<svg width="8" height="15" viewBox="0 0 6 10" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path fill-rule="evenodd" clip-rule="evenodd" d="M5.70711 0.292893C6.09763 0.683417 6.09763 1.31658 5.70711 1.70711L2.41421 5L5.70711 8.29289C6.09763 8.68342 6.09763 9.31658 5.70711 9.70711C5.31658 10.0976 4.68342 10.0976 4.29289 9.70711L0.292893 5.70711C-0.0976311 5.31658 -0.0976311 4.68342 0.292893 4.29289L4.29289 0.292893C4.68342 -0.0976311 5.31658 -0.0976311 5.70711 0.292893Z" fill="white"/>
</svg>
<div class="ml-4 FFF-White FFF-Equipe-Regular">Retour</div>
</a>';

// Titre principal
$content .= '<h1 style="margin-bottom:30px;letter-spacing:1px;" class="smartch_title FFF-Hero-Bold FFF-Blue">Gestion des Templates d\'Email</h1>';

// Bouton créer un nouveau template
$content .= '<div style="margin-bottom: 30px;">
    <a href="' . new moodle_url('/theme/remui/views/mailtemplates/edit.php') . '" class="btn-create">
        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Créer un nouveau template
    </a>
</div>';

// Récupérer tous les templates
$templates = $DB->get_records('smartch_mailtemplates', null, 'name ASC');

if (empty($templates)) {
    $content .= '<div style="text-align: center; padding: 50px; color: #666;">
        <svg width="80" height="80" fill="none" stroke="#ccc" viewBox="0 0 24 24" style="margin-bottom: 20px;">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"/>
        </svg>
        <p style="font-size: 18px; margin-bottom: 10px;">Aucun template créé</p>
        <p>Commencez par créer votre premier template d\'email</p>
    </div>';
} else {
    $content .= '<div class="template-grid">';

    foreach ($templates as $template) {
        $content .= '<div class="template-card">';

        // Badge du type, qu'on peut décommenter
        // $content .= '<div class="template-type-badge">' . htmlspecialchars($template->type) . '</div>';
        // $content .= '<div class="template-type-badge">' . htmlspecialchars(get_template_type_label($template->type)) . '</div>';
        // Contenu du template avec nom français
        // $content .= '<h3 style="color: #004686; margin-bottom: 15px; padding-right: 60px; margin-top: 35px;">' . htmlspecialchars(get_template_type_label($template->type)) . '</h3>';

        // affiche le nom du template
        $content .= '<h3 style="color: #004686; margin-bottom: 15px; padding-right: 60px; margin-top: 35px;">' . htmlspecialchars($template->name) . '</h3>';
        // Ajoute le type en petit sous-titre
        $content .= '<div style="color: #666; font-size: 12px; margin-bottom: 15px; text-transform: uppercase;">' . htmlspecialchars(get_template_type_label($template->type)) . '</div>';

        // Actions (éditer/supprimer)
        $content .= '<div class="template-actions">
            <a href="' . new moodle_url('/theme/remui/views/mailtemplates/edit.php?id=' . $template->id) . '" class="btn-action" title="Éditer">
                <svg width="20" height="20" fill="none" stroke="#004686" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                </svg>
            </a>
            <a href="' . new moodle_url('/theme/remui/views/mailtemplates/index.php?delete=' . $template->id) . '"
               class="btn-action" title="Supprimer"
               onclick="return confirm(\'Êtes-vous sûr de vouloir supprimer ce template ?\')">
                <svg width="20" height="20" fill="none" stroke="#dc3545" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
            </a>
        </div>';

        // Contenu du template
        // $content .= '<h3 style="color: #004686; margin-bottom: 15px; padding-right: 60px; margin-top: 35px;">' . htmlspecialchars($template->name) . '</h3>';

        //enlever l'aperçu du sujet
        /*  $content .= '<div style="background: #f8f9fa; padding: 10px; border-radius: 5px; margin-bottom: 15px;">
            <strong style="color: #004686;">Sujet:</strong><br>
            <span style="font-size: 14px;">' . htmlspecialchars($template->subject) . '</span>
        </div>';
		*/

        // Aperçu du contenu (tronqué) ==> enlever l'aperçu
        // $preview = strip_tags($template->content);
        // if (strlen($preview) > 100) {
        // $preview = substr($preview, 0, 100) . '...';
        // }
        $content .= '<div style="color: #666; font-size: 13px; line-height: 1.4;">
            ' . htmlspecialchars($preview) . '
        </div>';

        // Date de modification
        if (! empty($template->timemodified)) {
            $content .= '<div style="margin-top: 15px; font-size: 12px; color: #999;">
                Modifié le ' . date('d/m/Y à H:i', $template->timemodified) . '
            </div>';
        }

        $content .= '</div>';
    }

    $content .= '</div>';
}

echo $content;

echo $OUTPUT->footer();

//url: http://portailformation:8888/theme/remui/views/mailtemplates/index.php
//Base de données: table créée =>  mdl_smartch_email_templates  (Stockage templates)

/**
// Ces variables fonctionnent dans TOUS les contextes
'{{username}}'         // Toujours disponible
'{{firstname}}'         // Toujours disponible
'{{lastname}}'          // Toujours disponible
'{{email}}'            // Toujours disponible
'{{sitename}}'         // Toujours disponible
'{{date}}'             // Toujours disponible
'{{time}}'             // Toujours disponible
'{{datetime}}'         // Toujours disponible
'{{senderfirstname}}'  // Toujours disponible (toi qui envoies)
'{{senderlastname}}'   // Toujours disponible (toi qui envoies)

 */
