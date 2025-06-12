
<?php

require_once(__DIR__ . '/../../../config.php');

global $USER, $DB, $CFG;

echo $CFG->libdir;
// Charger les classes requises
require_once(__DIR__ . '/../../../calendar/lib.php');

// Créer un nouvel objet d'événement
$event = new calendar_event();

$event = new stdClass();
// $event->eventtype = SCORM_EVENT_TYPE_OPEN; // Constant defined somewhere in your code - this can be any string value you want. It is a way to identify the event.
$event->type = CALENDAR_EVENT_TYPE_STANDARD; // This is used for events we only want to display on the calendar, and are not needed on the block_myoverview.
$event->name = "Match 2";
$event->description = "RDV au stade george hebert";
$event->format = FORMAT_HTML;
$event->courseid = 2;
$event->groupid = 4;
$event->modulename = 0;
// $event->userid = 0;
$event->eventtype = 'group';
$event->timestart = 1690194265;
// $event->visible = 1;
$event->timeduration = 9600;

// Enregistrer l'événement dans la base de données
$event->id = calendar_event::create($event);

// Vérifier si l'événement a été créé avec succès
if ($event->id) {
    // L'événement a été créé avec succès
    echo "Événement ajouté avec succès !";
} else {
    // Erreur lors de la création de l'événement
    echo "Erreur lors de l'ajout de l'événement.";
}
