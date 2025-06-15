<?php

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot.'/lib/completionlib.php');
require_once($CFG->dirroot.'/theme/remui/views/utils.php');


global $DB;

//on va chercher le timestamp de minuit du début de la journée
$startofday = strtotime('today');
//on va chercher le timestamp de minuit de la fin de la journée
$endofday = strtotime('tomorrow');
//on va chercher les plannings de la journée
$plannings = $DB->get_records_sql('SELECT *
FROM mdl_smartch_planning sp
JOIN mdl_groups g ON sp.id = g.id
JOIN mdl_smartch_session ss ON sp.sessionid = ss.id
WHERE sp.startdate >= ? AND sp.enddate <= ?', array($startofday, $endofday));


//pour chaque planning
foreach($plannings as $planning){

    // var_dump($planning);
    // die();

    $activities = getCourseActivitiesFace2Face($planning->courseid);

    // on va chercher les sessions du planning
    $sessions = $DB->get_records_sql('SELECT *
    FROM mdl_smartch_session 
    WHERE groupid = ?', array($planning->id));

    //On va chercher la section du planning
    $section = $DB->get_record_sql('SELECT *
    FROM mdl_course_sections 
    WHERE id = ?', array($planning->sectionid));

    //pour chaque modules de la section
    $tableact = explode(',', $section->sequence);
    $tableact = array_map('intval', $tableact);

    foreach ($tableact as $val) {

        var_dump($val);

        //On va chercher l'activité si elle est de type face2face
        $activity = $DB->get_record_sql('SELECT *
        FROM mdl_course_modules cm
        JOIN mdl_modules m ON m.id = cm.module
        LEFT JOIN (
            SELECT a.id, a.name AS activityname, "face2face" AS activitytype, a.intro AS summary
            FROM mdl_face2face a
        ) activity ON activity.id = cm.instance AND activity.activitytype = m.name
        WHERE cm.id = ?
        AND activity.activitytype = "face2face"', array($val));

        // var_dump($activity);
        // die();

        if($activity){
            echo 'activity trouvée';
        }

        //pour chaque session
        foreach($sessions as $session){
            //on va chercher les utilisateurs de la session
            $users = $DB->get_records_sql('SELECT *
            FROM mdl_smartch_user_session 
            WHERE sessionid = ?', array($session->id));
            //pour chaque utilisateur
            foreach($users as $user){
                //on update son état de complétion
                face2face_get_completion_state($planning->courseid, $session->id, $user->id);
            }
        }

        // $targetId = $val; // L'ID que vous recherchez

        // $foundActivity = null;
        // $activity = null;

        // foreach ($activities as $activityy) {
        //     // var_dump($activityy->id . '||' .  $targetId . '/////');
        //     if ($activityy->id == $targetId) {
        //         // var_dump($activityy->activityid . $targetId . '/////');
        //         $activity = $activityy;
        //         break; // Sortir de la boucle dès que l'élément est trouvé
        //     }
        // }


        // //si il y a une session donc peut etre des plannings
        // if ($activity) {
        //     echo 'activity trouvée';
        //     if ($activity->activitytype == "face2face") {
        //         echo 'face2face';
        //     }
        // }
    }

    
}


///WORKING
// face2face_get_completion_state(23, 73, 1039);

/**
 * Obtains the automatic completion state for this face2face activity based on any conditions
 * in settings.
 *
 * @param object $course Course
 * @param object $cm Course-module
 * @param int $userid User ID
 * @param bool $type Type of comparison (or/and; can be used as return value if no conditions)
 * @return bool True if completed, false if not, $type if conditions not set.
 */
function face2face_get_completion_state($courseid, $moduleid, $userid) {
    global $DB, $CFG;

    $cm = get_coursemodule_from_id('face2face', $moduleid, 0, false, MUST_EXIST);
    $course = get_course($cm->course); // Récupérer l'objet cours complet

    echo "<hr>Vérification des données brutes de complétion :<br>";
    $records = $DB->get_records('course_modules_completion', 
    array('coursemoduleid' => $cm->id, 'userid' => $userid));
    var_dump($records);
    if(!$records){
        face2face_mark_completed($cm, $userid);
    }
    
    // Get face2face details
    $face2face = $DB->get_record('face2face', array('id' => $cm->instance), '*', MUST_EXIST);
    
    // Si l'option de complétion "passed" est activée
    if ($face2face->completionpassed) {
        $completion = new completion_info($course);

        // var_dump($completion);
        
        // Vérifier si la complétion est activée
        if (!$completion->is_enabled($cm)) {
            echo "La complétion n'est pas activée pour cette activité";
        } else {
            echo "La complétion est activée pour cette activité";
        }
        
        try {
            $completiondata = $completion->get_data($cm, false, $userid);
            var_dump($completiondata);
            if(!$completiondata){
                echo "Il n'a pas complété l'activité";
            }
            if ($completiondata && $completiondata->completionstate == COMPLETION_COMPLETE) {
                echo "Il a complété l'activité";
            } else {
                echo "Il n'a pas complété l'activité";
            }
            
        } catch (Exception $e) {
            echo "Erreur: " . $e->getMessage();
        }
    } else {
        // Completion option is not enabled so just return $type
        echo "L'option completionpassed n'est pas activée";
    }
}




/**
 * Marque une activité face2face comme complétée pour un utilisateur
 *
 * @param int $cmid ID du module de cours
 * @param int $userid ID de l'utilisateur
 * @return bool Succès ou échec
 */
function face2face_mark_completed($cm, $userid) {
    global $DB, $CFG;
    
    // Récupérer les objets nécessaires
    $course = get_course($cm->course);
    $face2face = $DB->get_record('face2face', array('id' => $cm->instance), '*', MUST_EXIST);

    // Vérifier si la complétion est activée
    $completion = new completion_info($course);

    var_dump($completion);
    
    if ($completion->is_enabled($cm) && $face2face->completionpassed) {
        echo "on update la completion";

        // Avant l'appel à update_state
        echo "Tentative de mise à jour directe dans la base de données<br>";
        $now = time();
        $record = new stdClass();
        $record->coursemoduleid = $cm->id;
        $record->userid = $userid;
        $record->completionstate = COMPLETION_COMPLETE;
        $record->timemodified = $now;
        $record->viewed = 1;

        // Vérifier si l'enregistrement existe déjà
        $existing = $DB->get_record('course_modules_completion', 
            array('coursemoduleid' => $cm->id, 'userid' => $userid));

        if ($existing) {
            $record->id = $existing->id;
            $result = $DB->update_record('course_modules_completion', $record);
            echo "Mise à jour de l'enregistrement existant: " . ($result ? "Réussi" : "Échec") . "<br>";
        } else {
            $result = $DB->insert_record('course_modules_completion', $record);
            echo "Insertion d'un nouvel enregistrement: " . ($result ? "Réussi (ID: $result)" : "Échec") . "<br>";
        }


        //not working....
        // Avant l'appel à update_state
        // $CFG->enablecompletion = true;
        // $CFG->enableavailability = true;
        // $completion->update_state($cm, COMPLETION_COMPLETE, $userid);
        return true;
    }
    
    return false;
}
