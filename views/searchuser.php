<?php


require_once(__DIR__ . '/../../../config.php');

global $DB, $CFG;

// require_once($CFG->dirroot . '/theme/remui/views/utils.php');

require_login();

$search = optional_param('search', null, PARAM_TEXT);
$cohortid = optional_param('cohortid', null, PARAM_INT);

$arrayusers = [];

//On va chercher les utilisateurs qui ne sont pas dans le groupe
$users = $DB->get_records_sql('SELECT DISTINCT u.id, u.firstname, u.lastname, u.email
FROM mdl_user u
LEFT JOIN mdl_cohort_members cm ON cm.userid = u.id AND cm.cohortid = '.$cohortid.'
WHERE u.deleted = 0 AND u.suspended = 0
AND (u.firstname LIKE "%'.$search.'%"
OR u.lastname LIKE "%'.$search.'%"
OR u.email LIKE "%'.$search.'%")
AND u.email <> "root@localhost"
AND cm.id IS NULL
LIMIT 0, 10', null);

foreach($users as $user){
    array_push($arrayusers, $user);
}

if (count($users) > 0) {
    $data['users'] = $arrayusers;
    $message = "Voici la liste des utilisateurs !";
} else {
    $data['users'] =  null;
    $message = "Pas d'utilisateurs trouvé";
}

$data['message'] = $message;

// Envoi de la réponse en JSON
header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");
echo json_encode($data);
