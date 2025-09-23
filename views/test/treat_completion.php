<?php

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot.'/lib/completionlib.php');

// Vérifier que l'utilisateur est administrateur
require_login();
require_capability('moodle/site:config', context_system::instance());

global $DB, $OUTPUT, $PAGE;

$PAGE->set_context(context_system::instance());
$PAGE->set_url('/theme/remui/views/test/treat_completion.php');
$PAGE->set_title('Traitement des complétions face2face');
$PAGE->set_heading('Traitement des complétions face2face');

// Paramètres configurables
$batch_size = isset($_GET['batch_size']) ? (int)$_GET['batch_size'] : 50;
$user_batch_size = isset($_GET['user_batch_size']) ? (int)$_GET['user_batch_size'] : 20;
$delay_ms = isset($_GET['delay']) ? (int)$_GET['delay'] : 100;
$auto_refresh = isset($_GET['auto_refresh']) ? (bool)$_GET['auto_refresh'] : false;

// Actions
$action = isset($_GET['action']) ? $_GET['action'] : 'show_stats';
$reset = isset($_GET['reset']) ? (bool)$_GET['reset'] : false;

echo $OUTPUT->header();

?>

<style>
.progress-container {
    background: #f8f9fa;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}
.progress-bar {
    width: 100%;
    height: 30px;
    background: #e9ecef;
    border-radius: 15px;
    overflow: hidden;
    margin: 10px 0;
}
.progress-fill {
    height: 100%;
    background: linear-gradient(90deg, #28a745, #20c997);
    transition: width 0.3s ease;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: bold;
}
.log-container {
    background: #000;
    color: #00ff00;
    padding: 15px;
    border-radius: 8px;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    max-height: 400px;
    overflow-y: auto;
    margin: 20px 0;
}
.stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
    margin: 20px 0;
}
.stat-card {
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 15px;
    text-align: center;
}
.stat-number {
    font-size: 2em;
    font-weight: bold;
    color: #007bff;
}
.controls {
    background: #fff;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    padding: 20px;
    margin: 20px 0;
}
.btn-group {
    margin: 10px 0;
}
.btn-group .btn {
    margin-right: 10px;
}
</style>

<?php if ($auto_refresh): ?>
<script>
setTimeout(function() {
    window.location.reload();
}, 5000); // Actualiser toutes les 5 secondes
</script>
<?php endif; ?>

<div class="container-fluid">
    <h2>🎯 Traitement des complétions face2face</h2>
    
    <?php
    
    // Réinitialiser si demandé
    if ($reset) {
        unset_config('completion_last_planning_id', 'theme_remui');
        echo '<div class="alert alert-success">✅ Progression réinitialisée</div>';
    }
    
    // Statistiques générales
    function get_completion_stats() {
        global $DB;
        
        $now = time();
        
        // Nombre total de plannings passés
        $total_plannings = $DB->count_records_sql('
            SELECT COUNT(DISTINCT sp.id)
            FROM {smartch_planning} sp
            JOIN {smartch_session} ss ON ss.id = sp.sessionid
            JOIN {groups} g ON g.id = ss.groupid
            WHERE sp.enddate < ?', array($now));
        
        // Plannings déjà traités
        $last_processed_id = get_config('theme_remui', 'completion_last_planning_id') ?: 0;
        $processed_plannings = $DB->count_records_sql('
            SELECT COUNT(DISTINCT sp.id)
            FROM {smartch_planning} sp
            JOIN {smartch_session} ss ON ss.id = sp.sessionid
            JOIN {groups} g ON g.id = ss.groupid
            WHERE sp.enddate < ? AND sp.id <= ?', array($now, $last_processed_id));
        
        // Plannings restants
        $remaining_plannings = $DB->count_records_sql('
            SELECT COUNT(DISTINCT sp.id)
            FROM {smartch_planning} sp
            JOIN {smartch_session} ss ON ss.id = sp.sessionid
            JOIN {groups} g ON g.id = ss.groupid
            WHERE sp.enddate < ? AND sp.id > ?', array($now, $last_processed_id));
        
        // Nombre d'activités face2face concernées
        $face2face_activities = $DB->count_records_sql('
            SELECT COUNT(DISTINCT cm.id)
            FROM {smartch_planning} sp
            JOIN {smartch_session} ss ON ss.id = sp.sessionid
            JOIN {groups} g ON g.id = ss.groupid
            JOIN {course_sections} cs ON cs.id = sp.sectionid
            JOIN {course_modules} cm ON FIND_IN_SET(cm.id, cs.sequence)
            JOIN {modules} m ON m.id = cm.module
            WHERE sp.enddate < ? AND m.name = "face2face"', array($now));
        
        return array(
            'total_plannings' => $total_plannings,
            'processed_plannings' => $processed_plannings,
            'remaining_plannings' => $remaining_plannings,
            'face2face_activities' => $face2face_activities,
            'progress_percent' => $total_plannings > 0 ? round(($processed_plannings / $total_plannings) * 100, 1) : 0
        );
    }
    
    $stats = get_completion_stats();
    
    ?>
    
    <!-- Statistiques -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['total_plannings']; ?></div>
            <div>Plannings totaux</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['processed_plannings']; ?></div>
            <div>Plannings traités</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['remaining_plannings']; ?></div>
            <div>Plannings restants</div>
        </div>
        <div class="stat-card">
            <div class="stat-number"><?php echo $stats['face2face_activities']; ?></div>
            <div>Activités face2face</div>
        </div>
    </div>
    
    <!-- Barre de progression -->
    <div class="progress-container">
        <h4>📊 Progression globale</h4>
        <div class="progress-bar">
            <div class="progress-fill" style="width: <?php echo $stats['progress_percent']; ?>%">
                <?php echo $stats['progress_percent']; ?>%
            </div>
        </div>
        <small>
            <?php echo $stats['processed_plannings']; ?> / <?php echo $stats['total_plannings']; ?> plannings traités
        </small>
    </div>
    
    <!-- Contrôles -->
    <div class="controls">
        <h4>⚙️ Contrôles</h4>
        
        <div class="btn-group">
            <a href="?action=show_stats" class="btn btn-info">📊 Actualiser les stats</a>
            <a href="?action=diagnostic" class="btn btn-warning">🔍 Diagnostic</a>
            <a href="?action=process&batch_size=<?php echo $batch_size; ?>&user_batch_size=<?php echo $user_batch_size; ?>&delay=<?php echo $delay_ms; ?>" 
               class="btn btn-success">▶️ Traiter un lot</a>
            <a href="?action=process_auto&batch_size=<?php echo $batch_size; ?>&user_batch_size=<?php echo $user_batch_size; ?>&delay=<?php echo $delay_ms; ?>&auto_refresh=1" 
               class="btn btn-primary">🔄 Traitement automatique</a>
            <a href="?reset=1" class="btn btn-warning" onclick="return confirm('Êtes-vous sûr de vouloir réinitialiser la progression ?')">🔄 Reset</a>
        </div>
        
        <div style="margin-top: 15px;">
            <form method="get" style="display: inline-block;">
                <input type="hidden" name="action" value="<?php echo $action; ?>">
                Taille lot: <input type="number" name="batch_size" value="<?php echo $batch_size; ?>" min="1" max="200" style="width: 80px;">
                Utilisateurs/lot: <input type="number" name="user_batch_size" value="<?php echo $user_batch_size; ?>" min="1" max="100" style="width: 80px;">
                Délai (ms): <input type="number" name="delay" value="<?php echo $delay_ms; ?>" min="0" max="5000" style="width: 80px;">
                <button type="submit" class="btn btn-sm btn-secondary">Appliquer</button>
            </form>
        </div>
    </div>
    
    <?php
    
    // Diagnostic : chercher toutes les activités face2face du système
    if ($action === 'diagnostic') {
        echo '<div class="log-container">';
        echo '<div>🔍 Diagnostic : Recherche de toutes les activités face2face...</div>';
        
        $face2face_activities = $DB->get_records_sql('
            SELECT cm.id, cm.course, cm.section, cs.name as section_name, cs.section as section_number, 
                   f.name as activity_name, c.fullname as course_name
            FROM {course_modules} cm
            JOIN {modules} m ON m.id = cm.module
            JOIN {face2face} f ON f.id = cm.instance
            JOIN {course_sections} cs ON cs.id = cm.section
            JOIN {course} c ON c.id = cm.course
            WHERE m.name = "face2face"
            ORDER BY cm.course, cs.section');
        
        echo '<div>📊 Nombre total d\'activités face2face trouvées: ' . count($face2face_activities) . '</div>';
        
        if (!empty($face2face_activities)) {
            echo '<div><strong>Liste des activités face2face :</strong></div>';
            foreach ($face2face_activities as $activity) {
                echo '<div>  🎯 ID: ' . $activity->id . ' | Cours: ' . $activity->course_name . ' | Section: ' . $activity->section_name . ' (n°' . $activity->section_number . ') | Activité: ' . $activity->activity_name . '</div>';
            }
        }
        
        // Vérifier les sections utilisées par les plannings
        echo '<div><br>🔍 Sections utilisées par les plannings passés :</div>';
        $planning_sections = $DB->get_records_sql('
            SELECT DISTINCT sp.sectionid, cs.name as section_name, cs.course, c.fullname as course_name,
                   COUNT(sp.id) as nb_plannings
            FROM {smartch_planning} sp
            JOIN {smartch_session} ss ON ss.id = sp.sessionid
            JOIN {course_sections} cs ON cs.id = sp.sectionid
            JOIN {course} c ON c.id = cs.course
            WHERE sp.enddate < ?
            GROUP BY sp.sectionid, cs.name, cs.course, c.fullname
            ORDER BY c.fullname, cs.section', array(time()));
        
        foreach ($planning_sections as $section) {
            echo '<div>  📁 Section ID: ' . $section->sectionid . ' | Cours: ' . $section->course_name . ' | Section: ' . $section->section_name . ' | Plannings: ' . $section->nb_plannings . '</div>';
        }
        
        echo '</div>';
    }
    
    // Traitement selon l'action
    if ($action === 'process' || $action === 'process_auto') {
        echo '<div class="log-container" id="log-container">';
        echo '<div>🚀 Début du traitement...</div>';
        
        $start_time = time();
        $processed_count = 0;
        $error_count = 0;
        
        // Récupérer le point de reprise
        $last_processed_id = get_config('theme_remui', 'completion_last_planning_id') ?: 0;
        
        // Traitement d'un lot
        $now = time();
        $plannings = $DB->get_records_sql('
            SELECT DISTINCT sp.id as planningid, sp.sectionid, sp.startdate, sp.enddate,
                   ss.id as sessionid, ss.groupid, g.courseid
            FROM {smartch_planning} sp
            JOIN {smartch_session} ss ON ss.id = sp.sessionid
            JOIN {groups} g ON g.id = ss.groupid
            WHERE sp.enddate < ? AND sp.id > ?
            ORDER BY sp.id ASC', 
            array($now, $last_processed_id), 0, $batch_size);
        
        if (empty($plannings)) {
            echo '<div>✅ Aucun planning à traiter - Terminé !</div>';
        } else {
            foreach ($plannings as $planning) {
                try {
                    echo '<div>📋 Traitement planning ID: ' . $planning->planningid . '</div>';
                    
                    // LOGIQUE HYBRIDE: Chercher les activités face2face
                    $course_id = $DB->get_field('course_sections', 'course', array('id' => $planning->sectionid));
                    if (!$course_id) {
                        echo '<div>⚠️ Cours non trouvé pour la section ID: ' . $planning->sectionid . '</div>';
                        continue;
                    }
                    
                    echo '<div>  🔍 Recherche des activités face2face dans le cours ID: ' . $course_id . '</div>';
                    
                    // Récupérer TOUTES les activités face2face du cours
                    $face2face_activities = $DB->get_records_sql('
                        SELECT cm.id, cm.section, cs.name as section_name, f.name as activity_name
                        FROM {course_modules} cm
                        JOIN {modules} m ON m.id = cm.module
                        JOIN {face2face} f ON f.id = cm.instance
                        JOIN {course_sections} cs ON cs.id = cm.section
                        WHERE m.name = "face2face" AND cs.course = ?
                        ORDER BY cm.section, cm.id', array($course_id));
                    
                    // Si aucune activité face2face dans le cours, chercher par correspondance de nom
                    if (empty($face2face_activities)) {
                        echo '<div>  ⚠️ Aucune activité face2face dans ce cours, recherche par correspondance...</div>';
                        
                        // Récupérer le nom du cours original
                        $original_course = $DB->get_record('course', array('id' => $course_id), 'fullname');
                        if ($original_course) {
                            echo '<div>    🔍 Cours original: ' . $original_course->fullname . '</div>';
                            
                            // Chercher des cours avec des noms similaires qui ont des activités face2face
                            $similar_courses = $DB->get_records_sql('
                                SELECT DISTINCT c.id, c.fullname, COUNT(cm.id) as activity_count
                                FROM {course} c
                                JOIN {course_sections} cs ON cs.course = c.id
                                JOIN {course_modules} cm ON cm.section = cs.id
                                JOIN {modules} m ON m.id = cm.module
                                WHERE m.name = "face2face" 
                                AND (c.fullname LIKE ? OR c.fullname LIKE ? OR c.fullname LIKE ?)
                                GROUP BY c.id, c.fullname
                                ORDER BY activity_count DESC
                                LIMIT 3', 
                                array(
                                    '%' . substr($original_course->fullname, 0, 20) . '%',
                                    '%' . substr($original_course->fullname, 0, 15) . '%',
                                    '%' . substr($original_course->fullname, 0, 10) . '%'
                                ));
                            
                            if (!empty($similar_courses)) {
                                echo '<div>    🎯 Cours similaires trouvés:</div>';
                                foreach ($similar_courses as $similar_course) {
                                    echo '<div>      - ' . $similar_course->fullname . ' (' . $similar_course->activity_count . ' activités)</div>';
                                }
                                
                                // Prendre le premier cours similaire avec le plus d'activités
                                $best_match = reset($similar_courses);
                                echo '<div>    ✅ Utilisation du cours: ' . $best_match->fullname . '</div>';
                                
                                // Récupérer les activités face2face du cours similaire
                                $face2face_activities = $DB->get_records_sql('
                                    SELECT cm.id, cm.section, cs.name as section_name, f.name as activity_name
                                    FROM {course_modules} cm
                                    JOIN {modules} m ON m.id = cm.module
                                    JOIN {face2face} f ON f.id = cm.instance
                                    JOIN {course_sections} cs ON cs.id = cm.section
                                    WHERE m.name = "face2face" AND cs.course = ?
                                    ORDER BY cm.section, cm.id', array($best_match->id));
                            }
                        }
                        
                        if (empty($face2face_activities)) {
                            echo '<div>  ❌ Aucune activité face2face trouvée même par correspondance</div>';
                            continue;
                        }
                    }
                    
                    echo '<div>  🎯 Trouvé ' . count($face2face_activities) . ' activités face2face dans le cours</div>';
                    $activities_processed = 0;
                    
                    // Traiter chaque activité face2face du cours
                    foreach ($face2face_activities as $activity) {
                        echo '<div>    🎯 Traitement activité: ' . $activity->activity_name . ' (Section: ' . $activity->section_name . ')</div>';
                        
                        $coursemodule = (object)array('id' => $activity->id, 'modname' => 'face2face');
                        
                        // Récupérer les utilisateurs
                        $users = $DB->get_records_sql('
                            SELECT DISTINCT u.id, u.email
                            FROM {groups_members} gm
                            JOIN {user} u ON u.id = gm.userid
                            WHERE gm.groupid = ? AND u.deleted = 0', 
                            array($planning->groupid));
                        
                        if (!empty($users)) {
                            echo '<div>      👥 ' . count($users) . ' utilisateurs à traiter</div>';
                            
                            $user_batches = array_chunk($users, $user_batch_size, true);
                            $users_processed = 0;
                            
                            foreach ($user_batches as $user_batch) {
                                foreach ($user_batch as $user) {
                                    // Vérifier/créer la complétion
                                    $existing = $DB->get_record('course_modules_completion', 
                                        array('coursemoduleid' => $coursemodule->id, 'userid' => $user->id));
                                    
                                    $completion_record = new stdClass();
                                    $completion_record->coursemoduleid = $coursemodule->id;
                                    $completion_record->userid = $user->id;
                                    $completion_record->completionstate = COMPLETION_COMPLETE;
                                    $completion_record->timemodified = $planning->enddate;
                                    $completion_record->viewed = 1;
                                    
                                    if ($existing) {
                                        if ($existing->completionstate != COMPLETION_COMPLETE) {
                                            $completion_record->id = $existing->id;
                                            $DB->update_record('course_modules_completion', $completion_record);
                                            $users_processed++;
                                        }
                                    } else {
                                        $DB->insert_record('course_modules_completion', $completion_record);
                                        $users_processed++;
                                    }
                                }
                            }
                            
                            echo '<div>      ✅ ' . $users_processed . ' complétions mises à jour pour cette activité</div>';
                        }
                        
                        $activities_processed++;
                    }
                    
                    echo '<div>  📊 ' . $activities_processed . ' activités traitées pour ce planning</div>';
                    
                    // Sauvegarder la progression
                    $last_processed_id = $planning->planningid;
                    $processed_count++;
                    
                } catch (Exception $e) {
                    echo '<div>❌ Erreur planning ' . $planning->planningid . ': ' . $e->getMessage() . '</div>';
                    $error_count++;
                    $last_processed_id = $planning->planningid;
                }
                
                // Délai entre les plannings
                if ($delay_ms > 0) {
                    usleep($delay_ms * 1000);
                }
                
                // Forcer l'affichage immédiat
                if (ob_get_level()) {
                    ob_flush();
                }
                flush();
            }
            
            // Sauvegarder la progression finale
            set_config('completion_last_planning_id', $last_processed_id, 'theme_remui');
        }
        
        $duration = time() - $start_time;
        echo '<div>🏁 Traitement terminé en ' . $duration . 's</div>';
        echo '<div>📊 Plannings traités: ' . $processed_count . ', Erreurs: ' . $error_count . '</div>';
        
        // Nouvelles statistiques
        $new_stats = get_completion_stats();
        echo '<div>📈 Progression: ' . $new_stats['progress_percent'] . '% (' . $new_stats['remaining_plannings'] . ' restants)</div>';
        
        echo '</div>';
        
        // Redirection automatique si traitement automatique et qu'il reste des plannings
        if ($action === 'process_auto' && $new_stats['remaining_plannings'] > 0) {
            echo '<script>
                setTimeout(function() {
                    window.location.href = "?action=process_auto&batch_size=' . $batch_size . '&user_batch_size=' . $user_batch_size . '&delay=' . $delay_ms . '&auto_refresh=1";
                }, 3000);
            </script>';
            echo '<div class="alert alert-info">🔄 Redirection automatique dans 3 secondes...</div>';
        }
    }
    
    ?>
    
</div>

<?php echo $OUTPUT->footer(); ?>
