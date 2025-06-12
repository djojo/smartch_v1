<?php

require_once("$CFG->libdir/formslib.php");

class messagegroup extends moodleform
{
    //Add elements to form
    public function definition()
    {
        global $CFG, $DB, $PAGE;
        $mform = $this->_form; // Don't forget the underscore!

        $context = $PAGE->context;

        // $mform->addElement('html', '<h1 style="margin-bottom:50px;letter-spacing:1px;" class="smartch_title FFF-Hero-Black FFF-Blue">Nouveau message pour le groupe ' . $this->_customdata['variables']['teamname'] . '</h1>');

        $returnurl = $this->_customdata['variables']['returnurl'];
        $teamid = $this->_customdata['variables']['teamid'];
        // $backurl = $this->_customdata['variables']['backurl'];

        // hidden
        $mform->addElement('hidden', 'returnurl', $returnurl);
        $mform->setType('returnurl', PARAM_TEXT); // Set the data type to integer

        $mform->addElement('hidden', 'teamid', $teamid);
        $mform->setType('teamid', PARAM_INT); // Set the data type to integer

        // $mform->addElement('hidden', 'backurl', $backurl);

        $options = array(
            'size' => '300',
            'maxlength' => '50',
            'class' => '',
            // 'required' => true
        );
        $mform->addElement('text', 'subject', 'Sujet du message', $options);
        $mform->addRule('subject', null, 'required', null, 'client');
        $mform->setType('subject', PARAM_TEXT); // Set the data type to integer


        $mform->addElement(
            'editor',
            'content',
            'Contenu',
            null,
            array('context' => $context)
        )->setValue(array('text' => ""));
        $mform->setType('content', PARAM_RAW);
        $mform->addRule('content', null, 'required', null, 'client');

        $this->add_action_buttons(false, "Envoyer");
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
