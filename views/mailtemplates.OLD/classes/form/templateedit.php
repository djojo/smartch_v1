<?php
// Formulaire Moodle

// Chemin vers le fichier de configuration Moodle
require_once $CFG->libdir . '/formslib.php'; //formslib.php = bibliothèque Moodle pour créer des formulaires

class templateedit extends moodleform
{
    // Hérite de moodleform = classe de base Moodle pour tous les formulaires
    // Formulaire de création/édition de template
    public function definition()
    {
        global $CFG;

        $mform = $this->_form;       // Objet formulaire Moodle
        $data  = $this->_customdata; // Données passées au formulaire

        // ID du template (pour l'édition) , Champ caché pour l'ID (édition)
        if (isset($data['id']) && $data['id']) {
            $mform->addElement('hidden', 'id', $data['id']);
            $mform->setType('id', PARAM_INT); // Validation : entier seulement
        }

        // Nom du template, Champ texte pour le nom
        $mform->addElement('text', 'name', 'Nom du template', 'maxlength="100" size="50" class="form-control"');
        $mform->setType('name', PARAM_TEXT); // Validation : texte
        $mform->addRule('name', 'Le nom est requis', 'required', null, 'client');
        $mform->addRule('name', 'Le nom ne peut pas dépasser 100 caractères', 'maxlength', 100, 'client'); // addRule() = validation côté client (JavaScript)
        if (isset($data['name'])) {
            $mform->setDefault('name', $data['name']);
        }

        // Type de template
        $types = [
            'general'      => 'Général',
            'inscription'  => 'Inscription',
            'validation'   => 'Validation',
            'rappel'       => 'Rappel',
            'completion'   => 'Fin de formation',
            'welcome'      => 'Bienvenue',
            'notification' => 'Notification',
        ];
        $mform->addElement('select', 'type', 'Type de template', $types, 'class="form-control"');
        $mform->setType('type', PARAM_TEXT);
        $mform->addRule('type', 'Le type est requis', 'required', null, 'client');
        if (isset($data['type'])) {
            $mform->setDefault('type', $data['type']);
        } else {
            $mform->setDefault('type', 'general');
        }

        // Sujet de l'email
        $mform->addElement('text', 'subject', 'Sujet de l\'email', 'maxlength="255" size="50" class="form-control"');
        $mform->setType('subject', PARAM_TEXT);
        $mform->addRule('subject', 'Le sujet est requis', 'required', null, 'client');
        $mform->addRule('subject', 'Le sujet ne peut pas dépasser 255 caractères', 'maxlength', 255, 'client');
        if (isset($data['subject'])) {
            $mform->setDefault('subject', $data['subject']);
        }

        // Corps de l'email (éditeur riche)
        $editoroptions = [
            'maxfiles'              => 0,     // Pas de fichiers uploadés
            'maxbytes'              => 0,     // Pas de limite de taille
            //'trusttext'           => false, // Pas de confiance aveugle (sécurité)
            'forcehttps'            => false, // Pas de forçage HTTPS
            'subdirs'               => false,
            'return_types'          => FILE_INTERNAL,
            'enable_filemanagement' => false,
        ];

        $mform->addElement('editor', 'content', 'Contenu de l\'email', null, $editoroptions);
        $mform->setType('content', PARAM_RAW);
        $mform->addRule('content', 'Le contenu de l\'email est requis', 'required', null, 'client');

        if (isset($data['content'])) {
            $mform->setDefault('content', ['text' => $data['content'], 'format' => FORMAT_HTML]);
        }

        // Boutons d'action
        $buttonarray   = [];
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', 'Enregistrer', 'class="btn btn-primary"');
        $buttonarray[] = $mform->createElement('cancel', 'cancel', 'Annuler', 'class="btn btn-secondary"');
        $mform->addGroup($buttonarray, 'buttonar', '', [' '], false);
    }

    // Validation du formulaire
    public function validation($data, $files)
    {
        $errors = parent::validation($data, $files);

        // Validation personnalisée du nom
        if (empty(trim($data['name']))) {
            $errors['name'] = 'Le nom du template ne peut pas être vide';
        }

        // Validation du sujet
        if (empty(trim($data['subject']))) {
            $errors['subject'] = 'Le sujet ne peut pas être vide';
        }

        // Validation du contenu
        if (empty(trim($data['content']['text']))) {
            $errors['content'] = 'Le contenu de l\'email ne peut pas être vide';
        }

        // Vérifier que le nom n'existe pas déjà (sauf pour l'édition)
        global $DB;
        $params = ['name' => trim($data['name'])];
        $sql    = "SELECT id FROM {smartch_mailtemplates} WHERE name = :name";

        if (isset($data['id']) && $data['id']) {
            $sql .= " AND id != :id";
            $params['id'] = $data['id'];
        }

        if ($DB->record_exists_sql($sql, $params)) {
            $errors['name'] = 'Un template avec ce nom existe déjà';
        }

        return $errors;
    }
}
