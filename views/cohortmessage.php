<?php

use tool_brickfield\local\areas\mod_choice\option;

require_once(__DIR__ . '/../../../config.php');
require_once('./utils.php');
require_once($CFG->dirroot . '/theme/remui/classes/form/messagecohort.php');

require_login();
isPortailRH();
isAdminFormation();

global $USER, $DB, $CFG;

$cohortid = required_param('cohortid', PARAM_INT);
$cohort = $DB->get_record('cohort', ['id' => $cohortid]);

$to_form = array('variables' => array('cohortid' => $cohortid));
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
        WHERE co.id = ' . $cohort->id . '
        AND u.deleted = 0 AND u.suspended = 0', null);
        //on va chercher l'utilisateur connecté
        $from = $DB->get_record('user', ['id' => $USER->id]);
        foreach($cohortmembers as $cohortmember){
            //on envoi le message à chaque membre sauf à l'utilisateur connecté
            if($USER->id != $cohortmember->id){
                email_to_user($cohortmember, $from, $fromform->subject, reset($fromform->content), reset($fromform->content));
            }
        }
        redirect($CFG->wwwroot . '/theme/remui/views/cohort.php?messagesent=' . count($cohortmembers) . '&cohortid='.$cohortid);
    } else {
        $content .= "Le groupe n'existe pas...";
    }
    
}



$context = context_system::instance();
$PAGE->set_url(new moodle_url('/theme/remui/views/cohortmessage.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title("Nouveau message");

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

$content .= '<div class="row mb-5">
<div class="col-md-12">
<h1 style="letter-spacing:1px;max-width:70%;cursor:pointer;" class="smartch_title FFF-Hero-Bold FFF-Blue">Nouveau message pour '.$cohort->name.'</h1>
</div>
</div>';

echo $content;

echo '<div class="row">
<div class="col-md-12">';
$mform->display();
echo '</div>
</div>';


echo $OUTPUT->footer();
