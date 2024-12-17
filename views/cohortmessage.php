<?php

use tool_brickfield\local\areas\mod_choice\option;

require_once(__DIR__ . '/../../../config.php');
require_once('./utils.php');
require_once($CFG->dirroot . '/theme/remui/classes/form/messagecohort.php');

require_login();
isAdminFormation();

global $USER, $DB, $CFG;
$content= "";

$cohortid = required_param('cohortid', PARAM_INT);
$cohort = $DB->get_record('cohort', ['id' => $cohortid]);

$courseid = optional_param('courseid', null, PARAM_INT);
$template = optional_param('template', null, PARAM_TEXT);

$context = context_system::instance();
$PAGE->set_url(new moodle_url('/theme/remui/views/cohortmessage.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title("Nouveau message");

$templatecontent = "";
$templatesubject = "";
if($courseid && $template == "notif"){

    $titleconfig = $DB->get_record_sql('SELECT * 
    FROM mdl_smartch_config sc
    WHERE sc.config_key = "email_template_subscribe_title"', null);
    if($titleconfig){
        $templatesubject = $titleconfig->config_value;
    }

    $contentconfig = $DB->get_record_sql('SELECT * 
    FROM mdl_smartch_config sc
    WHERE sc.config_key = "email_template_subscribe_content"', null);
    if($contentconfig){
        $templatecontent = $contentconfig->config_value;
    } else {
        $templatecontent = "";
    }

}


$to_form = array('variables' => array(
    'cohortid' => $cohortid,
    'templatesubject' => $templatesubject,
    'templatecontent' => $templatecontent
));

$mform = new create(null, $to_form);

if ($mform->is_cancelled()) {
    redirect($CFG->wwwroot . '/theme/remui/views/cohort.php?cohortid='.$cohortid);
} else if ($fromform = $mform->get_data()) {

    //on va chercher la cohort
    $cohort = $DB->get_record('cohort', ['id' => $fromform->cohortid]);

    if($cohort){

        //on va chercher les membres de la cohort
        $cohortmembers = $DB->get_records_sql('SELECT u.*
        FROM mdl_cohort co
        JOIN mdl_cohort_members cm ON cm.cohortid = co.id
        JOIN mdl_user u ON u.id = cm.userid
        WHERE co.id = ? 
        AND u.deleted = 0 AND u.suspended = 0', [$cohort->id]);
        //on va chercher l'utilisateur connecté
        $from = $DB->get_record('user', ['id' => $USER->id]);

        $msgsubject = $fromform->subject;
        $msgcontent = reset($fromform->content);
        
        foreach($cohortmembers as $cohortmember){
            try {
                if($USER->id != $cohortmember->id){
                    // echo $msgcontent;
                    // die();
                    email_to_user($cohortmember, $from, $msgsubject, $msgcontent, $msgcontent);
                }
            } catch (Exception $e) {
                // Gérer l'erreur
                debugging("Erreur d'envoi d'email: " . $e->getMessage());
            }
        }
        // die();
        redirect($CFG->wwwroot . '/theme/remui/views/cohort.php?messagesent=' . count($cohortmembers) . '&cohortid='.$cohortid);
    }
    
}





echo '<style>

#page{
    background:transparent !important;
}

#topofscroll {
    background: transparent !important;
    margin-top: 0px !important;
}

@media screen and (max-width: 830px) {
    #topofscroll{
        margin-top:40px !important;
    }
}

</style>';

echo $OUTPUT->header();

//le header avec bouton de retour au panneau admin
$templatecontextheader = (object)[
    'url' => new moodle_url('/theme/remui/views/cohort.php?cohortid='.$cohortid),
    'textcontent' => 'Retour au groupe'
];
$content .= $OUTPUT->render_from_template('theme_remui/smartch_header_back', $templatecontextheader);

$content .= '<div class="row" style="margin:50px 0;"></div>';

require_once('./cohortmenu.php');

$content .= '<div class="row mb-5 mt-4">
<div class="col-md-12">
<h4 style="letter-spacing:1px;max-width:70%;cursor:pointer;" class="FFF-Equipe-Bold FFF-Blue">Nouveau message pour '.$cohort->name.'</h4>
</div>
</div>';

echo $content;

echo '<div class="row">
<div class="col-md-12">';
$mform->display();
echo '</div>
</div>';


echo $OUTPUT->footer();
