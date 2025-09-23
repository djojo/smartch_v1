<?php

namespace theme_remui\task;

global $CFG;

require_once($CFG->dirroot . '/theme/remui/views/utils.php');
require_once($CFG->dirroot.'/lib/completionlib.php');

class daily_completion extends \core\task\scheduled_task {

    /**
     * Return the task's name as shown in admin screens.
     *
     * @return string
     */
    public function get_name() {
        return "Daily completion for session";
    }

    /**
     * Execute the task.
     * @return void
     */
    public function execute() {
        global $DB;

        mtrace('Début de la tâche de complétion quotidienne');
        
        try {
            // Calculer les timestamps pour la journée
            $startofday = strtotime('today');
            $endofday = strtotime('tomorrow');
            
            mtrace('Recherche des plannings pour la période: ' . date('Y-m-d H:i:s', $startofday) . ' à ' . date('Y-m-d H:i:s', $endofday));
            
            // Récupérer les plannings de la journée
            $plannings = $DB->get_records_sql('SELECT sp.*, g.courseid, ss.id as sessionid
            FROM mdl_smartch_planning sp
            JOIN mdl_groups g ON sp.id = g.id
            JOIN mdl_smartch_session ss ON sp.sessionid = ss.id
            WHERE sp.startdate >= ? AND sp.enddate <= ?', array($startofday, $endofday));

            if (empty($plannings)) {
                mtrace('Aucun planning trouvé pour cette période.');
                return;
            }
            
            mtrace('Nombre de plannings trouvés: ' . count($plannings));
            $processed_count = 0;
            $error_count = 0;

            // Traiter chaque planning
            foreach($plannings as $planning) {
                try {
                    $this->process_planning($planning);
                    $processed_count++;
                } catch (\Exception $e) {
                    $error_count++;
                    mtrace('Erreur lors du traitement du planning ID ' . $planning->id . ': ' . $e->getMessage());
                    // Continuer avec les autres plannings
                }
            }
            
            mtrace('Tâche terminée. Plannings traités: ' . $processed_count . ', Erreurs: ' . $error_count);
            
        } catch (\Exception $e) {
            mtrace('Erreur critique dans la tâche de complétion: ' . $e->getMessage());
            throw $e; // Re-lancer l'exception pour que la tâche soit marquée comme échouée
        }
    }
    
    /**
     * Traite un planning spécifique
     * @param object $planning
     */
    private function process_planning($planning) {
        global $DB;
        
        // Récupérer la session (optimisé: get_record au lieu de get_records)
        $session = $DB->get_record('smartch_session', array('id' => $planning->sessionid));
        if (!$session) {
            mtrace('Session non trouvée pour le planning ID: ' . $planning->id);
            return;
        }

        // Récupérer la section du planning
        $section = $DB->get_record('course_sections', array('id' => $planning->sectionid));
        if (!$section || empty($section->sequence)) {
            mtrace('Section non trouvée ou vide pour le planning ID: ' . $planning->id);
            return;
        }

        // Traiter les modules de la section
        $tableact = explode(',', $section->sequence);
        $tableact = array_filter(array_map('intval', $tableact)); // Filtrer les valeurs vides

        foreach ($tableact as $moduleid) {
            try {
                $this->process_module($moduleid, $session, $planning->courseid);
            } catch (\Exception $e) {
                mtrace('Erreur lors du traitement du module ID ' . $moduleid . ': ' . $e->getMessage());
                // Continuer avec les autres modules
            }
        }
    }
    
    /**
     * Traite un module spécifique
     * @param int $moduleid
     * @param object $session
     * @param int $courseid
     */
    private function process_module($moduleid, $session, $courseid) {
        global $DB;
        
        // Vérifier si c'est une activité face2face
        $activity = $DB->get_record_sql('SELECT cm.*, m.name as modulename, f.completionpassed
        FROM mdl_course_modules cm
        JOIN mdl_modules m ON m.id = cm.module
        JOIN mdl_face2face f ON f.id = cm.instance
        WHERE cm.id = ? AND m.name = "face2face"', array($moduleid));

        if (!$activity) {
            return; // Pas une activité face2face, on passe
        }

        // Récupérer les utilisateurs de la session (optimisé)
        $users = $DB->get_records_sql('SELECT u.id, u.email
        FROM mdl_smartch_session ss
        JOIN mdl_groups g ON g.id = ss.groupid
        JOIN mdl_groups_members gm ON gm.groupid = g.id
        JOIN mdl_user u ON u.id = gm.userid
        WHERE ss.id = ? AND u.deleted = 0', array($session->id));
        
        if (empty($users)) {
            mtrace('Aucun utilisateur trouvé pour la session ID: ' . $session->id);
            return;
        }
        
        mtrace('Traitement de l\'activité face2face ID ' . $moduleid . ' pour ' . count($users) . ' utilisateurs');
        
        // Traiter chaque utilisateur
        foreach($users as $user) {
            try {
                $this->face2face_get_completion_state_smartch($courseid, $moduleid, $user->id);
            } catch (\Exception $e) {
                mtrace('Erreur lors du traitement de l\'utilisateur ' . $user->email . ': ' . $e->getMessage());
                // Continuer avec les autres utilisateurs
            }
        }
    }


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
    /**
     * Vérifie et met à jour l'état de complétion pour une activité face2face
     * @param int $courseid ID du cours
     * @param int $moduleid ID du module
     * @param int $userid ID de l'utilisateur
     */
    private function face2face_get_completion_state_smartch($courseid, $moduleid, $userid) {
        global $DB;

        try {
            // Récupérer le module de cours
            $cm = get_coursemodule_from_id('face2face', $moduleid, 0, false, MUST_EXIST);
            $course = get_course($cm->course);
            
            // Vérifier si l'enregistrement de complétion existe déjà
            $existing_completion = $DB->get_record('course_modules_completion', 
                array('coursemoduleid' => $cm->id, 'userid' => $userid));
            
            if (!$existing_completion) {
                // Aucun enregistrement de complétion, on le crée
                $this->face2face_mark_completed($cm, $userid);
                return;
            }
            
            // Récupérer les détails de l'activité face2face
            $face2face = $DB->get_record('face2face', array('id' => $cm->instance), '*', MUST_EXIST);
            
            // Vérifier si l'option de complétion "passed" est activée
            if (!$face2face->completionpassed) {
                mtrace('L\'option completionpassed n\'est pas activée pour l\'activité ID: ' . $cm->id);
                return;
            }
            
            $completion = new \completion_info($course);

            // Vérifier si la complétion est activée pour ce module
            if (!$completion->is_enabled($cm)) {
                mtrace('La complétion n\'est pas activée pour l\'activité ID: ' . $cm->id);
                return;
            }
            
            // Vérifier l'état actuel de complétion
            $completiondata = $completion->get_data($cm, false, $userid);
            
            if ($completiondata && $completiondata->completionstate == COMPLETION_COMPLETE) {
                // Déjà complété, rien à faire
                return;
            }
            
            // Marquer comme complété si ce n'est pas déjà fait
            $this->face2face_mark_completed($cm, $userid);
            
        } catch (\Exception $e) {
            mtrace('Erreur lors de la vérification de complétion pour l\'utilisateur ' . $userid . ', module ' . $moduleid . ': ' . $e->getMessage());
            throw $e;
        }
    }




    /**
     * Marque une activité face2face comme complétée pour un utilisateur
     *
     * @param int $cmid ID du module de cours
     * @param int $userid ID de l'utilisateur
     * @return bool Succès ou échec
     */
    /**
     * Marque une activité face2face comme complétée pour un utilisateur
     * @param object $cm Module de cours
     * @param int $userid ID de l'utilisateur
     * @return bool Succès ou échec
     */
    private function face2face_mark_completed($cm, $userid) {
        global $DB;
        
        try {
            // Récupérer les objets nécessaires
            $course = get_course($cm->course);
            $face2face = $DB->get_record('face2face', array('id' => $cm->instance), '*', MUST_EXIST);

            // Vérifier si la complétion est activée
            $completion = new \completion_info($course);
            
            if (!$completion->is_enabled($cm)) {
                mtrace('La complétion n\'est pas activée pour le module ID: ' . $cm->id);
                return false;
            }
            
            if (!$face2face->completionpassed) {
                mtrace('L\'option completionpassed n\'est pas activée pour l\'activité ID: ' . $cm->id);
                return false;
            }
            
            // Utiliser une transaction pour assurer la cohérence
            $transaction = $DB->start_delegated_transaction();
            
            try {
                $now = time();
                $record = new \stdClass();
                $record->coursemoduleid = $cm->id;
                $record->userid = $userid;
                $record->completionstate = COMPLETION_COMPLETE;
                $record->timemodified = $now;
                $record->viewed = 1;

                // Vérifier si l'enregistrement existe déjà
                $existing = $DB->get_record('course_modules_completion', 
                    array('coursemoduleid' => $cm->id, 'userid' => $userid));

                if ($existing) {
                    // Mettre à jour l'enregistrement existant
                    $record->id = $existing->id;
                    $result = $DB->update_record('course_modules_completion', $record);
                    mtrace('Complétion mise à jour pour l\'utilisateur ' . $userid . ', module ' . $cm->id);
                } else {
                    // Créer un nouvel enregistrement
                    $result = $DB->insert_record('course_modules_completion', $record);
                    mtrace('Complétion créée pour l\'utilisateur ' . $userid . ', module ' . $cm->id . ' (ID: ' . $result . ')');
                }
                
                // Valider la transaction
                $transaction->allow_commit();
                
                // Déclencher l'événement de complétion
                $context = \context_module::instance($cm->id);
                $event = \core\event\course_module_completion_updated::create(array(
                    'objectid' => $cm->id,
                    'context' => $context,
                    'relateduserid' => $userid,
                    'other' => array(
                        'relateduserid' => $userid
                    )
                ));
                $event->add_record_snapshot('course_modules', $cm);
                $event->trigger();
                
                return true;
                
            } catch (\Exception $e) {
                $transaction->rollback($e);
                throw $e;
            }
            
        } catch (\Exception $e) {
            mtrace('Erreur lors de la mise à jour de complétion pour l\'utilisateur ' . $userid . ', module ' . $cm->id . ': ' . $e->getMessage());
            return false;
        }
    }

}
