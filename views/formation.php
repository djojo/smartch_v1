<?php

require_once(__DIR__ . '/../../../config.php');
require_once('./utils.php');

require_login();

global $USER, $DB, $CFG;

$group = null;
$session = null;
$completion = '';

$iscategoryfree = false;

//On va chercher le rôle le plus haut de l'utilisateur
$rolename = getMainRole();

$sent = optional_param('sent', false, PARAM_BOOL);
$courseid = required_param('id', PARAM_INT);
$userid = optional_param('userid', '', PARAM_INT);
$sectionid = optional_param('sectionid', null, PARAM_INT);
$messagesent = optional_param('messagesent', null, PARAM_TEXT);

if (!$userid) {
    $userid = $USER->id;
}

if (!$courseid) {
    redirect($CFG->wwwroot . '/');
}

$course = $DB->get_record('course', ['id' => $courseid]);
$category = $DB->get_record('course_categories', ['id' => $course->category]);

//on recupère les champs personnalisés
$diplomeresult = $DB->get_records_sql('
SELECT cd.value 
FROM mdl_customfield_data cd
JOIN mdl_customfield_field cf ON cf.id = cd.fieldid
WHERE cd.instanceid = ' . $courseid . ' AND cf.shortname = "diplome"', null);
$diplomeobject = reset($diplomeresult);
if ($diplomeobject) {
    $diplome = $diplomeobject->value;
}


$coursetyperesult = $DB->get_records_sql('
SELECT cd.value 
FROM mdl_customfield_data cd
JOIN mdl_customfield_field cf ON cf.id = cd.fieldid
WHERE cd.instanceid = ' . $courseid . ' AND cf.shortname = "coursetype"', null);
$coursetypeobject = reset($coursetyperesult);
if ($coursetypeobject) {
    $coursetype = $coursetypeobject->value;
}


$coursedurationresult = $DB->get_records_sql('
SELECT cd.value 
FROM mdl_customfield_data cd
JOIN mdl_customfield_field cf ON cf.id = cd.fieldid
WHERE cd.instanceid = ' . $courseid . ' AND cf.shortname = "courseduration"', null);
$coursedurationobject = reset($coursedurationresult);
if ($coursedurationobject) {
    $courseduration = $coursedurationobject->value;
}

$context = context_system::instance();
$PAGE->set_url(new moodle_url('/theme/remui/views/formation.php', array('id' => $courseid)));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title($course->fullname);

echo $OUTPUT->header();

if($messagesent){
    displayNotification('Message envoyé');
}

echo '<style>
.main-inner {
    margin-top: 0px !important;
}
#topofscroll{
    margin-top:0px !important;
}
@media screen and (max-width: 830px) {
    #topofscroll{
        padding-top:50px !important;
    }
}
</style>';

$content = "";


if (!$rolename) {
    $rolename = "student";
}


//on divise les écrans en fonction des rôles
if ($rolename == "super-admin" || $rolename == "manager") {
    //pour super admin et admin formateur
    require_once('./formation_admin.php');
} else if ($rolename == "teacher" || $rolename == "smalleditingteacher" || $rolename == "editingteacher") {
    if(hasResponsablePedagogiqueRole()){
        //pour responsable pedagogique
        require_once('./formation_formateur.php');
    } else {
        //On va chercher le role sur la formation
        $rolename = getUserRoleFromCourse($courseid);
        if($rolename == "teacher"){
            //pour formateur
            require_once('./formation_formateur.php');
        }else{
            //pour les etudiants
            require_once('./formation_student.php');
        }
    }
    
} else if ($rolename == "student") {
    require_once('./formation_student.php');
} else {
    require_once('./formation_student.php');
}

echo $content;


echo $OUTPUT->footer();
