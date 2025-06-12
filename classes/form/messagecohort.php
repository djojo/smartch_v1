<?php

require_once("$CFG->libdir/formslib.php");

class create extends moodleform
{
    //Add elements to form
    public function definition()
    {
        global $CFG, $DB, $PAGE;
        $mform = $this->_form; // Don't forget the underscore!

        $context = $PAGE->context;

        $cohortid = $this->_customdata['variables']['cohortid'];
        $templatecontent = $this->_customdata['variables']['templatecontent'];
        $templatesubject = $this->_customdata['variables']['templatesubject'];
        // $cohort = $DB->get_record('cohort', ['id' => $cohortid]);

        $mform->addElement('hidden', 'cohortid', $cohortid);
        $mform->setType('cohortid', PARAM_INT); // Set the data type to integer

        $options = array(
            'size' => '300',
            'maxlength' => '50',
            'class' => '',
            // 'required' => true
        );
        $mform->addElement('text', 'subject', 'Sujet du message', $options);
        $mform->addRule('subject', null, 'required', null, 'client');
        $mform->setType('subject', PARAM_TEXT); // Set the data type to integer
        $mform->setDefault('subject', $templatesubject);

        $mform->addElement(
            'editor',
            'content',
            'Contenu',
            null,
            array('context' => $context)
        )->setValue(array('text' => $templatecontent));
        $mform->setType('content', PARAM_RAW);
        $mform->addRule('content', null, 'required', null, 'client');

        $this->add_action_buttons(true, "Envoyer");
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
