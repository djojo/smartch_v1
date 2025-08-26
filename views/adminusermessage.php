<?php

require_once __DIR__ . '/../../../config.php';
require_once './utils.php';

require_once $CFG->dirroot . '/theme/remui/classes/form/messageuser.php';
require_once $CFG->libdir . '/messagelib.php';

require_login();

global $USER, $DB, $CFG;

//On va chercher le rôle le plus haut de l'utilisateur
$rolename = getMainRole();

$content  = "";
$userid   = optional_param('userid', '', PARAM_INT);
$teamid   = optional_param('teamid', '', PARAM_INT);
$courseid = optional_param('courseid', '', PARAM_INT);
$return   = optional_param('return', 'adminmenu', PARAM_TEXT);
// $backurl = optional_param('backurl', 'adminmenu', PARAM_TEXT);
// $backurl = urldecode($backurl);
// $backurl = new moodle_url($backurl);

//on var chercher l'utilisateur
$userprofile = $DB->get_record('user', ['id' => $userid]);

$context = context_system::instance();
$PAGE->set_url(new moodle_url('/theme/remui/views/adminusermessage.php'));
$PAGE->set_context(\context_system::instance());
$PAGE->set_title("Nouveau message pour " . $userprofile->firstname . ' ' . $userprofile->lastname);

$to_form = ['variables' => ['userid' => $userid, 'firstname' => $userprofile->firstname, 'lastname' => $userprofile->lastname, 'return' => $return, 'courseid' => $courseid, 'teamid' => $teamid]];
$mform   = new messageuser(null, $to_form);

if ($mform->is_cancelled()) {
    echo '<script>javascript: history.go(-2)</script>';
    // require_once('./redirections.php');
    // redirect($mform->get_data()->backurl);
    // redirect($CFG->wwwroot . '/theme/remui/views/adminuser.php?userid=' . $mform->get_data()->userid, "");
} else if ($fromform = $mform->get_data()) {

    // DEBUG TEMPORAIRE
   // echo "<pre>DONNÉES DU FORMULAIRE:";
   // var_dump($fromform);
    //echo "</pre>";
    //die();

    $teamid   = $fromform->teamid;
    $courseid = $fromform->courseid;
    $return   = $fromform->return;

    $userfor = $DB->get_record('user', ['id' => $fromform->userid]);
    //pour les tests
    // $userfor = $DB->get_record('user', ['id' => 32601]);
    $userbase = $DB->get_record('user', ['id' => $USER->id]);

    $message                    = new \core\message\message();
    $message->courseid          = 1;
    $message->component         = 'moodle';
    $message->name              = 'instantmessage';
    $message->userfrom          = $userbase;
    $message->userto            = $userfor;
    $message->subject           = $fromform->subject;
    $message->fullmessage       = reset($fromform->content);
    $message->fullmessageformat = FORMAT_MARKDOWN;
    $message->fullmessagehtml   = reset($fromform->content);
    $message->smallmessage      = reset($fromform->content); //rajouter substring
    $message->notification      = '0';

    $messageid = message_send($message);

    // echo '<script>javascript: history.go(-2)</script>';

    //========ANCIEN CODE
    /*$subject = 'Nouveau message de ' . $userbase->firstname . ' ' . $userbase->lastname . ' : ' . $fromform->subject;
    $body = reset($fromform->content);
    $from = 'Portail Formation FFF';

    //on envoi un mail à l'utilisateur
    email_to_user($userfor, $from, $subject, $body, $body); */

    //======= Utiliser le système de templates
    // Utiliser le système de templates
    // NOUVEAU CODE AVEC TEMPLATES :
    $template_choice = $fromform->template ?? 'default';

    if ($template_choice === 'default') {
        // Ancien système (fallback)
        $subject = 'Nouveau message de ' . $userbase->firstname . ' ' . $userbase->lastname . ' : ' . $fromform->subject;
        $body    = reset($fromform->content);
        $from    = 'Portail Formation FFF';
        email_to_user($userfor, $from, $subject, $body, $body);
    } else {
        // Nouveau système avec templates
        $variables = [
            '{{username}}'        => $userfor->username,
            '{{firstname}}'       => $userfor->firstname,
            '{{lastname}}'        => $userfor->lastname,
            '{{senderfirstname}}' => $userbase->firstname,
            '{{senderlastname}}'  => $userbase->lastname,
            '{{message}}'         => reset($fromform->content),
            '{{subject}}'         => $fromform->subject,
        ];

        $result = send_template_email($userfor, $template_choice, $variables);

        // Si le template échoue, utiliser l'ancien système
        if (! $result) {
            $subject = 'Nouveau message de ' . $userbase->firstname . ' ' . $userbase->lastname . ' : ' . $fromform->subject;
            $body    = reset($fromform->content);
            $from    = 'Portail Formation FFF';
            email_to_user($userfor, $from, $subject, $body, $body);
        }
    }
    // var_dump($fromform->return . ' - ' . $fromform->courseid . ' - ' . $fromform->teamid);

    if ($fromform->courseid) {
        redirect($CFG->wwwroot . '/theme/remui/views/formation.php?id=' . $fromform->courseid . '&sent=true');
    } else if ($fromform->teamid) {
        redirect($CFG->wwwroot . '/theme/remui/views/adminteam.php?teamid=' . $fromform->teamid . '&sent=true');
    } else {
        redirect($CFG->wwwroot . '/theme/remui/views/adminuser.php?userid=' . $fromform->userid . '&sent=true');
    }

    // redirect($backurl, "Message envoyé");
    // $return = $fromform->return;
    // $courseid = $fromform->$courseid;
    // require_once('./redirections.php');
    // redirect($CFG->wwwroot . '/theme/remui/views/adminuser.php?userid=' . $fromform->userid, "Message envoyé");
}

echo $OUTPUT->header();

echo '<style>
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

// echo html_writer::start_div('container');

//le header avec bouton de retour au panneau admin
// $templatecontextheader = (object)[
//     'url' => $backurl,
//     'textcontent' => 'Retour'
// ];
// $content .= $OUTPUT->render_from_template('theme_remui/smartch_header_back', $templatecontextheader);

$content .= '<div style="font-size:0.8rem;cursor: pointer; display: flex; align-items: center; position: absolute; top: 120px;" onclick="history.back()">
<svg width="8" height="15" viewBox="0 0 6 10" fill="none" xmlns="http://www.w3.org/2000/svg">
    <path fill-rule="evenodd" clip-rule="evenodd" d="M5.70711 0.292893C6.09763 0.683417 6.09763 1.31658 5.70711 1.70711L2.41421 5L5.70711 8.29289C6.09763 8.68342 6.09763 9.31658 5.70711 9.70711C5.31658 10.0976 4.68342 10.0976 4.29289 9.70711L0.292893 5.70711C-0.0976311 5.31658 -0.0976311 4.68342 0.292893 4.29289L4.29289 0.292893C4.68342 -0.0976311 5.31658 -0.0976311 5.70711 0.292893Z" fill="white"/>
</svg>
<div class="ml-4 FFF-White FFF-Equipe-Regular">Retour</div>
</div>';

echo $content;

$mform->display();

// $content .= html_writer::end_div(); //container

// echo $content;

echo $OUTPUT->footer();
