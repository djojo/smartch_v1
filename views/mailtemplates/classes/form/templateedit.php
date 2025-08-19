<?php
// Formulaire Moodle

// Chemin vers le fichier de configuration Moodle
require_once($CFG->libdir . '/formslib.php');

class templateedit extends moodleform {
    // Formulaire de création/édition de template
    public function definition() {
        global $CFG;

        $mform = $this->_form;
        $data = $this->_customdata;

        // ID du template (pour l'édition)
        if (isset($data['id']) && $data['id']) {
            $mform->addElement('hidden', 'id', $data['id']);
            $mform->setType('id', PARAM_INT);
        }

        // Nom du template
        $mform->addElement('text', 'name', 'Nom du template', 'maxlength="100" size="50" class="form-control"');
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', 'Le nom est requis', 'required', null, 'client');
        $mform->addRule('name', 'Le nom ne peut pas dépasser 100 caractères', 'maxlength', 100, 'client');
        if (isset($data['name'])) {
            $mform->setDefault('name', $data['name']);
        }

        // Type de template
        $types = array(
            'general' => 'Général',
            'inscription' => 'Inscription',
            'validation' => 'Validation',
            'rappel' => 'Rappel',
            'completion' => 'Fin de formation',
            'welcome' => 'Bienvenue',
            'notification' => 'Notification'
        );
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
        $editoroptions = array(
            'maxfiles' => 0,
            'maxbytes' => 0,
            'trusttext' => false,
            'forcehttps' => false,
            'subdirs' => false,
            'return_types' => FILE_INTERNAL,
            'enable_filemanagement' => false
        );

        $mform->addElement('editor', 'content', 'Contenu de l\'email', null, $editoroptions);
        $mform->setType('content', PARAM_RAW);
        $mform->addRule('content', 'Le contenu de l\'email est requis', 'required', null, 'client');
        
        if (isset($data['content'])) {
            $mform->setDefault('content', array('text' => $data['content'], 'format' => FORMAT_HTML));
        }

        // Boutons d'action
        $buttonarray = array();
        $buttonarray[] = $mform->createElement('submit', 'submitbutton', 'Enregistrer', 'class="btn btn-primary"');
        $buttonarray[] = $mform->createElement('cancel', 'cancel', 'Annuler', 'class="btn btn-secondary"');
        $mform->addGroup($buttonarray, 'buttonar', '', array(' '), false);
    }

	// Validation du formulaire
    public function validation($data, $files) {
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
        $params = array('name' => trim($data['name']));
        $sql = "SELECT id FROM {smartch_mailtemplates} WHERE name = :name";
        
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