<?php

require_once(__DIR__ . '/../../../config.php');
require_once('./utils.php');

require_login();

$message = "";
$subject = optional_param('subject', "", PARAM_TEXT);
$body = optional_param('body', "", PARAM_TEXT);

global $USER, $DB, $CFG, $PAGE;

if ($subject && $body) {

    $portail = getConfigPortail();
    if ($portail == "portailrh") {
        $emailSupport = 'servicedigital.ieff@fff.fr';
    } else {
        $emailSupport = 'omonchery@fff.fr';
    }

    //on créer l'objet user
    $senduser = new stdClass();
    $senduser->email = $emailSupport;
    $senduser->firstname = "Portail";
    $senduser->lastname = "Formation FFF";
    $senduser->maildisplay = true;
    $senduser->mailformat = 1; // 0 (zero) text-only emails, 1 (one) for HTML/Text emails.
    $senduser->id = -99; // Moodle User ID. If it is for someone who is not a Moodle user, use an invalid ID like -99.
    $senduser->firstnamephonetic = "Portail";
    $senduser->lastnamephonetic = "Formation FFF";
    $senduser->middlename = "";
    $senduser->alternatename = "";

    if ($senduser) {
        $from = $USER->email;
        //On envoi un mail
        email_to_user($senduser, $from, $subject, $body, $body);
        $message = "message envoyé";
    } else {
        $message = "Il n'y pas de contact pour le support,<br/> veuillez contacter directement par mail <a href='mailto:servicedigital.ieff@fff.fr'>servicedigital.ieff@fff.fr</a>";
    }
}

$context = context_system::instance();
$PAGE->set_url(new moodle_url('/theme/remui/views/support.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title("Contacter le support");

echo $OUTPUT->header();

$content = "";

$content .= "<style>

img.FFF_background_header{
    display:none;
}

#page-footer{
    z-index:20;
}
</style>";


// $content .= '<img class="FFF_background_header" src="https://iff-uat.smartchlab.fr/theme/remui/pix/background-header.png">';
if ($message) {
    $content .= '<div id="page" style="margin: 0;background-image:url(\'' . new moodle_url('/theme/remui/pix/background-header.png') . '\');background-size: cover;height:100vh;width:100vw;top: 0;text-align: center;left: 0;position: fixed;">

<div style="background: white; position: fixed; top: 50%; padding: 30px 80px; border-radius: 15px; max-width: 90vw; width: 600px; left: 50%; transform: translate(-50%, -50%);">

<h2 class="FFF-Hero-Bold" style="margin: 30px 0;text-transform:uppercase; color:#004685;letter-spacing:2px;padding:0 20px;">Contactez le support</h2>
<h5>' . $message . '</h5>
<a href="' . new moodle_url('/') . '" style="margin:20px 0;" class="smartch_btn">Retour</a>
</div>

</div>    ';
} else {

    $content .= '<div id="page" style="margin: 0;background-image:url(\'' . new moodle_url('/theme/remui/pix/background-header.png') . '\');background-size: cover;height:100vh;width:100vw;top: 0;text-align: center;left: 0;position: fixed;">

    <div style="background: white; position: fixed; top: 50%; padding: 30px 80px; border-radius: 15px; max-width: 90vw; width: 600px; left: 50%; transform: translate(-50%, -50%);">
    
    <h2 class="FFF-Hero-Bold" style="margin: 30px 0;text-transform:uppercase; color:#004685;letter-spacing:2px;padding:0 20px;">Contactez le support</h2>
    <form method="POST" action="' . new moodle_url('/theme/remui/views/support.php') . '">
    <select name="subject" style="padding: 0 20px;margin: 20px 0;" class="form-control">
        <option>Sujet</option>
        <option>Question pour le support</option>
        <option>Rapporter un bug</option>
    </select>
    <textarea name="body" rows="4" cols="30" class="form-control" placeholder="Contenu du message">
    </textarea>
    <div style="text-align:center;">
    <button type="submit" style="margin:20px 0;" class="smartch_btn">Envoyer</button>
    </div>
    </form>
    </div>
    
    </div>    ';
}


echo $content;


echo $OUTPUT->footer();
