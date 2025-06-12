<?php

require_once(__DIR__ . '/../../../config.php');
require_once('./utils.php');

require_login();

global $USER, $DB, $CFG;

$content = '';

//On va chercher le rôle le plus haut de l'utilisateur
$rolename = getMainRole();

isStudent();

$context = context_system::instance();
$PAGE->set_url(new moodle_url('/theme/remui/views/calendar.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title("Calendrier");

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


// $content .= html_writer::start_div('container');

//le header avec bouton de retour au panneau admin
$templatecontextheader = (object)[
    'url' => new moodle_url('/theme/remui/views/adminmenu.php'),
    'textcontent' => 'Retour au panneau d\'administration'
];
$content .= $OUTPUT->render_from_template('theme_remui/smartch_header_back', $templatecontextheader);


$content .= $OUTPUT->render_from_template('theme_remui/smartch_calendar', null);

// $content .= html_writer::end_div(); //container

echo $content;

echo $OUTPUT->footer();
