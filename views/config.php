<?php

require_once(__DIR__ . '/../../../config.php');
require_once('./utils.php');

require_login();

global $USER, $DB, $CFG;

$content = '';

//On va chercher le rôle le plus haut de l'utilisateur
$rolename = getMainRole();

isAdmin();

$portailtype = optional_param('portailtype', null, PARAM_TEXT);
if($portailtype){
    $portail = $DB->get_record_sql('SELECT * 
    FROM mdl_smartch_config sc
    WHERE sc.config_key = "portail"', null);

    if(!empty($portail)){
        //On modifie le portail
        $portail->config_value = $portailtype;
        $DB->update_record('smartch_config', $portail);
    }
}

$context = context_system::instance();
$PAGE->set_url(new moodle_url('/theme/remui/views/config.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title("Configuration");


echo  '<style>
    .collapsible-actions{
        display:none !important;
    }
    #page.drawers .main-inner {
        margin-top: 150px;
        margin-bottom: 3.5rem;
    }
    .fff-course-box-info-details{
        top:-100px;
        position:absolute;
    }
    div[role=main] {
        margin-top: 0 !important;
    }
</style>';

echo $OUTPUT->header();

// //le header avec bouton de retour au panneau admin
// $templatecontextheader = (object)[
//     'url' => new moodle_url('/theme/remui/views/adminmenu.php'),
//     'textcontent' => 'Retour au panneau d\'administration'
// ];
// $content .= $OUTPUT->render_from_template('theme_remui/smartch_header_back', $templatecontextheader);


$content .= '<a href="' . new moodle_url('/theme/remui/views/adminmenu.php') . '" style="font-size:0.8rem;cursor: pointer; display: flex; align-items: center; position: absolute; top: 120px;">
<svg width="8" height="15" viewBox="0 0 6 10" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path fill-rule="evenodd" clip-rule="evenodd" d="M5.70711 0.292893C6.09763 0.683417 6.09763 1.31658 5.70711 1.70711L2.41421 5L5.70711 8.29289C6.09763 8.68342 6.09763 9.31658 5.70711 9.70711C5.31658 10.0976 4.68342 10.0976 4.29289 9.70711L0.292893 5.70711C-0.0976311 5.31658 -0.0976311 4.68342 0.292893 4.29289L4.29289 0.292893C4.68342 -0.0976311 5.31658 -0.0976311 5.70711 0.292893Z" fill="white"/>
</svg>
<div class="ml-4 FFF-White FFF-Equipe-Regular">Retour</div>
</a>';

//on va chercher la config
$configportail = getConfigPortail();

$content .= '<div class="row">';
$content .= '<div class="col-md-12">';
$content .= '<h1 style="margin-bottom:50px;letter-spacing:1px;" class="smartch_title FFF-Hero-Bold FFF-Blue">Configuration</h1>';
$content .= '<form method="POST" action=""  style="text-align:center;">';
$content .= '<div>';
$content .= '<select class="smartch_select" name="portailtype">';
if($configportail == "portailformation"){
    $content .= '<option selected value="portailformation">Portail Formation</option>';
    $content .= '<option value="portailrh">Portail RH</option>';
} else{
    $content .= '<option value="portailformation">Portail Formation</option>';
    $content .= '<option selected value="portailrh">Portail RH</option>';
}

$content .= '</select>';
$content .= '</div>';

$content .= '<input class="smartch_btn" type="submit" value="Valider">';
$content .= '</form>';
$content .= '</div>';
$content .= '</div>';



echo $content;

echo $OUTPUT->footer();
