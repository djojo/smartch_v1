<?php

use tool_brickfield\local\areas\mod_choice\option;

require_once(__DIR__ . '/../../../config.php');
require_once('./utils.php');
// require_once($CFG->dirroot.'/enrol/cohort/lib.php');
// require_once($CFG->dirroot.'/group/lib.php');

require_login();
isPortailRH();
isAdminFormation();

global $USER, $DB, $CFG;

$cohortid = required_param('cohortid', PARAM_INT);
$cohort = $DB->get_record('cohort', ['id' => $cohortid]);

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

$content .= '<div class="row">
<div class="col-md-12">
<h1 style="letter-spacing:1px;max-width:70%;cursor:pointer;" class="smartch_title FFF-Hero-Bold FFF-Blue">Nouveau message pour '.$cohort->name.'</h1>
</div>
</div>';



echo $content;

echo $OUTPUT->footer();

//pour la pagination
echo '<script>

// window.onload = function(){
//     var els = document.getElementsByClassName("page' . $pageno . '");
//     Array.from(els).forEach((el) => {
//         el.setAttribute("selected", "selected");
//     });
// };

    var els = document.getElementsByClassName("page' . $pageno . '");
    Array.from(els).forEach((el) => {
        el.setAttribute("selected", "selected");
    });

</script>';
