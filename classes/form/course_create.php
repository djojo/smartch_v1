<?php

require_once("$CFG->libdir/formslib.php");

class create extends moodleform
{
    //Add elements to form
    public function definition()
    {
        global $CFG, $DB;
        $mform = $this->_form; // Don't forget the underscore!

        $mform->addElement('text', 'fullname', 'Nom de la formation');
        $mform->setType('fullname', PARAM_TEXT);
        $mform->addRule('fullname', null, 'required', null, 'client');

        $mform->addElement('text', 'shortname', 'Nom abrégé de la formation');
        $mform->setType('shortname', PARAM_TEXT);
        $mform->addRule('shortname', null, 'required', null, 'client');

        $mform->addElement(
            'editor',
            'summary',
            'Courte description de la formation',
            null,
            array('context' => $context)
        )->setValue(array('text' => ""));
        $mform->setType('summary', PARAM_RAW);
        

        $categories = $DB->get_records_sql('SELECT * 
        FROM mdl_course_categories', NULL);
        $sectionsoptions = [];
        for ($i = 1; $i <= 20; $i++) {
            $sectionsoptions[$i] = $i;
        }
        $mform->addElement('select', 'nbsection', "Nombre de sections", $sectionsoptions);


        $categories = $DB->get_records_sql('SELECT * 
        FROM mdl_course_categories', NULL);
        $catoptions = [];
        foreach($categories as $cat){
            $catoptions[$cat->id] = $cat->name;
        }
        $mform->addElement('select', 'categoryid', "Catégorie", $catoptions);


        $typeoptions = array(
            'classe' => 'Mode classe',
            'ampitheatre' => 'Mode Ampithéatre'
        );
        $mform->addElement('select', 'subscribemethod', "Méthode d'inscription", $typeoptions);

        $mform->addElement(
            'filepicker',
            'image',
            'Image',
            null,
            array(
                'subdirs' => 0, 'areamaxbytes' => 11111111111111, 'maxfiles' => 1,
                'accepted_types' => array('.png', '.jpg', '.jpeg')
            )
        );

        $this->add_action_buttons();
    }



    //Custom validation should be added here
    function validation($data, $files)
    {
        return array();
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
