<?php
/**
 * Script de diagnostic pour identifier les plannings problématiques
 * qui bloquent le traitement automatique
 */

require(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/theme/remui/views/utils.php');

require_login();
$context = context_system::instance();
require_capability('moodle/site:config', $context);

$PAGE->set_url(new moodle_url('/theme/remui/views/test/diagnostic_plannings_problematiques.php'));
$PAGE->set_context($context);
$PAGE->set_title('Diagnostic Plannings Problématiques');
$PAGE->set_heading('Diagnostic Plannings Problématiques');

echo $OUTPUT->header();

?>

<style>
    .diagnostic-container {
        max-width: 1400px;
        margin: 20px auto;
        padding: 20px;
    }
    .stat-card {
        background: white;
        border-radius: 8px;
        padding: 20px;
        margin-bottom: 20px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .stat-card h3 {
        margin-top: 0;
        color: #333;
    }
    .problem-table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
    }
    .problem-table th,
    .problem-table td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #ddd;
    }
    .problem-table th {
        background: #f5f5f5;
        font-weight: bold;
    }
    .problem-table tr:hover {
        background: #f9f9f9;
    }
    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
    }
    .badge-error {
        background: #ffebee;
        color: #c62828;
    }
    .badge-warning {
        background: #fff3e0;
        color: #e65100;
    }
    .badge-success {
        background: #e8f5e9;
        color: #2e7d32;
    }
</style>

<div class="diagnostic-container">
    <h2>🔍 Diagnostic des Plannings Problématiques</h2>
    
    <?php
    
    $now = time();
    $last_processed_id = get_config('theme_remui', 'completion_last_planning_id') ?: 0;
    
    echo '<div class="stat-card">';
    echo '<h3>📊 État actuel</h3>';
    echo '<p><strong>Dernier planning traité ID:</strong> ' . $last_processed_id . '</p>';
    echo '<p><strong>Date actuelle:</strong> ' . date('Y-m-d H:i:s', $now) . '</p>';
    echo '</div>';
    
    // 1. Plannings sans cours associé
    echo '<div class="stat-card">';
    echo '<h3>❌ Problème 1: Plannings dont la section n\'a pas de cours</h3>';
    
    $plannings_sans_cours = $DB->get_records_sql('
        SELECT sp.id as planningid, sp.sectionid, sp.startdate, sp.enddate, 
               ss.id as sessionid, ss.groupid
        FROM {smartch_planning} sp
        JOIN {smartch_session} ss ON ss.id = sp.sessionid
        WHERE sp.enddate < ? 
        AND sp.id > ?
        AND NOT EXISTS (
            SELECT 1 FROM {course_sections} cs 
            WHERE cs.id = sp.sectionid AND cs.course IS NOT NULL
        )
        ORDER BY sp.id ASC
        LIMIT 100', 
        array($now, $last_processed_id));
    
    $count_sans_cours = count($plannings_sans_cours);
    echo '<p><strong>Nombre de plannings concernés:</strong> <span class="badge badge-error">' . $count_sans_cours . '</span></p>';
    
    if ($count_sans_cours > 0) {
        echo '<table class="problem-table">';
        echo '<thead><tr>';
        echo '<th>Planning ID</th>';
        echo '<th>Section ID</th>';
        echo '<th>Session ID</th>';
        echo '<th>Group ID</th>';
        echo '<th>Date fin</th>';
        echo '</tr></thead><tbody>';
        
        $shown = 0;
        foreach ($plannings_sans_cours as $p) {
            if ($shown >= 20) {
                echo '<tr><td colspan="5"><em>... et ' . ($count_sans_cours - 20) . ' autres</em></td></tr>';
                break;
            }
            echo '<tr>';
            echo '<td>' . $p->planningid . '</td>';
            echo '<td>' . $p->sectionid . '</td>';
            echo '<td>' . $p->sessionid . '</td>';
            echo '<td>' . $p->groupid . '</td>';
            echo '<td>' . date('Y-m-d H:i', $p->enddate) . '</td>';
            echo '</tr>';
            $shown++;
        }
        echo '</tbody></table>';
    }
    echo '</div>';
    
    // 2. Plannings avec cours mais sans activité face2face
    echo '<div class="stat-card">';
    echo '<h3>⚠️ Problème 2: Plannings avec cours mais sans activité face2face</h3>';
    
    $plannings_sans_f2f = $DB->get_records_sql('
        SELECT sp.id as planningid, sp.sectionid, sp.startdate, sp.enddate,
               ss.id as sessionid, ss.groupid, cs.course as courseid, c.fullname
        FROM {smartch_planning} sp
        JOIN {smartch_session} ss ON ss.id = sp.sessionid
        JOIN {course_sections} cs ON cs.id = sp.sectionid
        JOIN {course} c ON c.id = cs.course
        WHERE sp.enddate < ?
        AND sp.id > ?
        AND NOT EXISTS (
            SELECT 1 FROM {course_modules} cm
            JOIN {modules} m ON m.id = cm.module
            WHERE m.name = "face2face" AND cm.course = cs.course
        )
        ORDER BY sp.id ASC
        LIMIT 100',
        array($now, $last_processed_id));
    
    $count_sans_f2f = count($plannings_sans_f2f);
    echo '<p><strong>Nombre de plannings concernés:</strong> <span class="badge badge-warning">' . $count_sans_f2f . '</span></p>';
    
    if ($count_sans_f2f > 0) {
        echo '<table class="problem-table">';
        echo '<thead><tr>';
        echo '<th>Planning ID</th>';
        echo '<th>Cours ID</th>';
        echo '<th>Nom du cours</th>';
        echo '<th>Session ID</th>';
        echo '<th>Date fin</th>';
        echo '</tr></thead><tbody>';
        
        $shown = 0;
        foreach ($plannings_sans_f2f as $p) {
            if ($shown >= 20) {
                echo '<tr><td colspan="5"><em>... et ' . ($count_sans_f2f - 20) . ' autres</em></td></tr>';
                break;
            }
            echo '<tr>';
            echo '<td>' . $p->planningid . '</td>';
            echo '<td>' . $p->courseid . '</td>';
            echo '<td>' . htmlspecialchars(substr($p->fullname, 0, 50)) . '</td>';
            echo '<td>' . $p->sessionid . '</td>';
            echo '<td>' . date('Y-m-d H:i', $p->enddate) . '</td>';
            echo '</tr>';
            $shown++;
        }
        echo '</tbody></table>';
    }
    echo '</div>';
    
    // 3. Plannings OK (avec cours ET activité face2face)
    echo '<div class="stat-card">';
    echo '<h3>✅ Plannings OK (avec cours ET activité face2face)</h3>';
    
    $plannings_ok = $DB->get_records_sql('
        SELECT sp.id as planningid, sp.sectionid, cs.course as courseid, c.fullname,
               COUNT(DISTINCT cm.id) as nb_f2f
        FROM {smartch_planning} sp
        JOIN {smartch_session} ss ON ss.id = sp.sessionid
        JOIN {course_sections} cs ON cs.id = sp.sectionid
        JOIN {course} c ON c.id = cs.course
        JOIN {course_modules} cm ON cm.course = cs.course
        JOIN {modules} m ON m.id = cm.module AND m.name = "face2face"
        WHERE sp.enddate < ?
        AND sp.id > ?
        GROUP BY sp.id, sp.sectionid, cs.course, c.fullname
        ORDER BY sp.id ASC
        LIMIT 20',
        array($now, $last_processed_id));
    
    $count_ok = count($plannings_ok);
    echo '<p><strong>Nombre de plannings OK (échantillon):</strong> <span class="badge badge-success">' . $count_ok . '</span></p>';
    
    if ($count_ok > 0) {
        echo '<table class="problem-table">';
        echo '<thead><tr>';
        echo '<th>Planning ID</th>';
        echo '<th>Cours ID</th>';
        echo '<th>Nom du cours</th>';
        echo '<th>Nb activités F2F</th>';
        echo '</tr></thead><tbody>';
        
        foreach ($plannings_ok as $p) {
            echo '<tr>';
            echo '<td>' . $p->planningid . '</td>';
            echo '<td>' . $p->courseid . '</td>';
            echo '<td>' . htmlspecialchars(substr($p->fullname, 0, 50)) . '</td>';
            echo '<td>' . $p->nb_f2f . '</td>';
            echo '</tr>';
        }
        echo '</tbody></table>';
    }
    echo '</div>';
    
    // 4. Résumé et recommandations
    echo '<div class="stat-card">';
    echo '<h3>💡 Résumé et Recommandations</h3>';
    
    $total_problemes = $count_sans_cours + $count_sans_f2f;
    
    echo '<p><strong>Total de plannings problématiques identifiés:</strong> ' . $total_problemes . '</p>';
    
    if ($total_problemes > 0) {
        echo '<div style="background: #fff3cd; border-left: 4px solid #ffc107; padding: 15px; margin-top: 15px;">';
        echo '<h4 style="margin-top: 0;">🔧 Actions recommandées:</h4>';
        echo '<ol>';
        echo '<li><strong>Plannings sans cours (' . $count_sans_cours . '):</strong> Ces plannings ont des sections invalides. Ils seront maintenant automatiquement ignorés et marqués comme traités.</li>';
        echo '<li><strong>Plannings sans face2face (' . $count_sans_f2f . '):</strong> Ces cours n\'ont pas d\'activités face2face. Ils seront également ignorés et marqués comme traités.</li>';
        echo '<li><strong>Relancer le traitement:</strong> Avec les corrections apportées, le script ne devrait plus boucler sur ces plannings problématiques.</li>';
        echo '</ol>';
        echo '</div>';
    } else {
        echo '<div style="background: #d4edda; border-left: 4px solid #28a745; padding: 15px; margin-top: 15px;">';
        echo '<p style="margin: 0;">✅ Aucun planning problématique détecté dans les 100 prochains plannings à traiter.</p>';
        echo '</div>';
    }
    
    echo '</div>';
    
    // 5. Statistiques globales
    $total_plannings = $DB->count_records_sql('
        SELECT COUNT(DISTINCT sp.id)
        FROM {smartch_planning} sp
        JOIN {smartch_session} ss ON ss.id = sp.sessionid
        WHERE sp.enddate < ?',
        array($now));
    
    $plannings_traites = $DB->count_records_sql('
        SELECT COUNT(DISTINCT sp.id)
        FROM {smartch_planning} sp
        JOIN {smartch_session} ss ON ss.id = sp.sessionid
        WHERE sp.enddate < ? AND sp.id <= ?',
        array($now, $last_processed_id));
    
    $plannings_restants = $total_plannings - $plannings_traites;
    $progress_percent = $total_plannings > 0 ? round(($plannings_traites / $total_plannings) * 100, 2) : 0;
    
    echo '<div class="stat-card">';
    echo '<h3>📈 Progression globale</h3>';
    echo '<p><strong>Total plannings à traiter:</strong> ' . $total_plannings . '</p>';
    echo '<p><strong>Plannings traités:</strong> ' . $plannings_traites . ' (' . $progress_percent . '%)</p>';
    echo '<p><strong>Plannings restants:</strong> ' . $plannings_restants . '</p>';
    echo '</div>';
    
    ?>
    
    <div style="margin-top: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px;">
        <h3>🔄 Actions disponibles</h3>
        <p>
            <a href="treat_completion.php?action=process_auto&batch_size=50&user_batch_size=20&delay=100&auto_refresh=1" 
               class="btn btn-primary" style="margin-right: 10px;">
                ▶️ Relancer le traitement automatique
            </a>
            <a href="treat_completion.php" class="btn btn-secondary">
                📊 Voir la page de traitement
            </a>
        </p>
    </div>
</div>

<?php

echo $OUTPUT->footer();
