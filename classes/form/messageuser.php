<?php

require_once "$CFG->libdir/formslib.php";

class messageuser extends moodleform
{
    //Add elements to form
    public function definition()
    {
        global $CFG, $DB, $PAGE;
        $mform = $this->_form; // Don't forget the underscore!

        $context = $PAGE->context;

        // CORRIGÉ : Récupération cohérente des variables
        $variables = $this->_customdata['variables'];
        $return = $variables['return'] ?? '';      // Correspond à adminusermessage.php
        $userid = $variables['userid'] ?? 0;
        $teamid = $variables['teamid'] ?? 0;       // Ajouté
        $courseid = $variables['courseid'] ?? 0;   // Ajouté

        // Récupérer tous les templates pour la liste déroulante
        $templates = get_all_templates();
        $template_options = ['default' => 'Message simple (sans template)'];
        foreach ($templates as $template) {
            $template_options[$template->name] = $template->name . ' (' . $template->type . ')';
        }

        // CORRIGÉ : Champs cachés avec noms cohérents
        $mform->addElement('hidden', 'return', $return);
        $mform->setType('return', PARAM_TEXT);
        
        $mform->addElement('hidden', 'userid', $userid);
        $mform->setType('userid', PARAM_INT);
        
        $mform->addElement('hidden', 'teamid', $teamid);
        $mform->setType('teamid', PARAM_INT);
        
        $mform->addElement('hidden', 'courseid', $courseid);
        $mform->setType('courseid', PARAM_INT);

        // Template selector
        $mform->addElement('select', 'template', 'Template à utiliser', $template_options);
        $mform->setType('template', PARAM_TEXT);

        // Sujet du message
        $options = [
            'size'      => '300',
            'maxlength' => '50',
            'class'     => '',
        ];
        $mform->addElement('text', 'subject', 'Sujet du message', $options);
        $mform->addRule('subject', null, 'required', null, 'client');
        $mform->setType('subject', PARAM_TEXT);

        // Contenu du message
        $mform->addElement(
            'editor',
            'content',
            'Contenu',
            null,
            ['context' => $context]
        )->setValue(['text' => ""]);
        $mform->setType('content', PARAM_RAW);
        $mform->addRule('content', null, 'required', null, 'client');

        $this->add_action_buttons(true, "Envoyer");
    }

    //Custom validation should be added here
    public function validation($data, $files)
    {
        return [];
    }

    /**
     * Process the form submission, used if form was submitted via AJAX
     *
     * This method can return scalar values or arrays that can be json-encoded, they will be passed to the caller JS.
     *
     * Submission data can be accessed as: $this->get_data()
     *
     * @return mixed
     */
    // public function process_dynamic_submission() {
    //     file_postupdate_standard_filemanager($this->get_data(), 'files',
    //         $this->get_options(), $this->get_context_for_dynamic_submission(), 'user', 'private', 0);
    //     return null;
    // }

    /**
     * Load in existing data as form defaults
     *
     * Can be overridden to retrieve existing values from db by entity id and also
     * to preprocess editor and filemanager elements
     *
     * Example:
     *     $this->set_data(get_entity($this->_ajaxformdata['id']));
     */
    // public function set_data_for_dynamic_submission(): void {
    //     $data = new \stdClass();
    //     file_prepare_standard_filemanager($data, 'files', $this->get_options(),
    //         $this->get_context_for_dynamic_submission(), 'user', 'private', 0);
    //     $this->set_data($data);
    // }

}
