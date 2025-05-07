<?php

require_once(__DIR__ . '/../../../../config.php');

require_login();

$username = 'michel@dumas.com';
$issuerid = 1;

//On va chercher l'utilisateur
$user = $DB->get_record_sql('SELECT * FROM mdl_user WHERE username = ?', array($username));

if ($user) {

    //on va chercher le oauth2 via id
    $oauth2Userid = $DB->get_record_sql('SELECT l.*
    FROM mdl_auth_oauth2_linked_login l
    WHERE l.userid = ?', array($user->id));
    if($oauth2Userid) {
        //On supprime le linkOauth2
        $DB->delete_records('auth_oauth2_linked_login', array('id' => $oauth2Userid->id));
    }

    //on va chercher le oauth2 du username
    $oauth2Username = $DB->get_record_sql('SELECT l.*
    FROM mdl_auth_oauth2_linked_login l
    WHERE l.userid = ?', array($user->id));
    if($oauth2Username) {
        //On supprime le linkOauth2
        $DB->delete_records('auth_oauth2_linked_login', array('id' => $oauth2Username->id));
    }

    //on va chercher le oauth2 via email
    $oauth2Email = $DB->get_record_sql('SELECT l.*
    FROM mdl_auth_oauth2_linked_login l
    WHERE l.userid = ?', array($user->id));
    if($oauth2Email) {
        //On supprime le linkOauth2
        $DB->delete_records('auth_oauth2_linked_login', array('id' => $oauth2Email->id));
    }


    //On créer le linkOauth2
    $userinfo = [
        'firstname' => $user->firstname,
        'lastname' => $user->lastname,
        'username' => $user->username,
        'email' => $user->email,
        'descriptions' => 'auth via smartch link auth',
    ];

    $issuer = \core\oauth2\api::get_issuer($issuerid);

    $success = \auth_oauth2\api::link_login($userinfo, $issuer, $user->id, true);

    if ($success) {
        $message = 'SUCCESS - ' . $user->firstname . ' ' . $user->lastname . ' a été linké !';
    } else {
        $message = 'ERROR - Problème lors de l\'utilisation de l\'api \auth_oauth2\api::link_login...';
    }
} else {
    $message = 'ERROR - Utilisateur introuvable via le username : ' . $username . '...';
}

