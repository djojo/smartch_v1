<?php

require_once(__DIR__ . '/../../../../config.php');
require_login();
require_capability('moodle/site:config', context_system::instance());

global $DB;

echo "<!DOCTYPE html><html><head><title>Debug Completion</title></head><body>";
echo "<h1>🔍 Debug du traitement des complétions</h1>";

// 1. Vérifier les statistiques de base
echo "<h2>📊 Statistiques de base</h2>";

$now = time();
echo "<p><strong>Timestamp actuel:</strong> " . $now . " (" . date('Y-m-d H:i:s', $now) . ")</p>";

// Nombre total de plannings passés
$total_plannings = $DB->count_records_sql('
    SELECT COUNT(DISTINCT sp.id)
    FROM {smartch_planning} sp
    JOIN {smartch_session} ss ON ss.id = sp.sessionid
    JOIN {groups} g ON g.id = ss.groupid
    WHERE sp.enddate < ?', array($now));

echo "<p><strong>Plannings passés (total):</strong> " . $total_plannings . "</p>";

// Point de reprise actuel
$last_processed_id = get_config('theme_remui', 'completion_last_planning_id') ?: 0;
echo "<p><strong>Dernier planning traité (ID):</strong> " . $last_processed_id . "</p>";

// Plannings restants
$remaining_plannings = $DB->count_records_sql('
    SELECT COUNT(DISTINCT sp.id)
    FROM {smartch_planning} sp
    JOIN {smartch_session} ss ON ss.id = sp.sessionid
    JOIN {groups} g ON g.id = ss.groupid
    WHERE sp.enddate < ? AND sp.id > ?', array($now, $last_processed_id));

echo "<p><strong>Plannings restants:</strong> " . $remaining_plannings . "</p>";

// 2. Afficher les prochains plannings à traiter
echo "<h2>📋 Prochains plannings à traiter (10 premiers)</h2>";

$next_plannings = $DB->get_records_sql('
    SELECT DISTINCT sp.id as planningid, sp.sectionid, sp.startdate, sp.enddate,
           ss.id as sessionid, ss.groupid, g.courseid, c.fullname as course_name
    FROM {smartch_planning} sp
    JOIN {smartch_session} ss ON ss.id = sp.sessionid
    JOIN {groups} g ON g.id = ss.groupid
    JOIN {course} c ON c.id = g.courseid
    WHERE sp.enddate < ? AND sp.id > ?
    ORDER BY sp.id ASC', 
    array($now, $last_processed_id), 0, 10);

if (empty($next_plannings)) {
    echo "<p>✅ Aucun planning à traiter - Traitement terminé !</p>";
} else {
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Planning ID</th><th>Section ID</th><th>Session ID</th><th>Group ID</th><th>Course ID</th><th>Cours</th><th>Date fin</th></tr>";
    
    foreach ($next_plannings as $planning) {
        echo "<tr>";
        echo "<td>" . $planning->planningid . "</td>";
        echo "<td>" . $planning->sectionid . "</td>";
        echo "<td>" . $planning->sessionid . "</td>";
        echo "<td>" . $planning->groupid . "</td>";
        echo "<td>" . $planning->courseid . "</td>";
        echo "<td>" . htmlspecialchars($planning->course_name) . "</td>";
        echo "<td>" . date('Y-m-d H:i:s', $planning->enddate) . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 3. Vérifier les activités face2face
echo "<h2>🎯 Activités face2face disponibles</h2>";

$face2face_count = $DB->count_records_sql('
    SELECT COUNT(*)
    FROM {course_modules} cm
    JOIN {modules} m ON m.id = cm.module
    WHERE m.name = "face2face"');

echo "<p><strong>Nombre total d'activités face2face:</strong> " . $face2face_count . "</p>";

// Activités face2face par cours
$face2face_by_course = $DB->get_records_sql('
    SELECT c.id, c.fullname, COUNT(cm.id) as nb_activities
    FROM {course} c
    JOIN {course_modules} cm ON cm.course = c.id
    JOIN {modules} m ON m.id = cm.module
    WHERE m.name = "face2face"
    GROUP BY c.id, c.fullname
    ORDER BY nb_activities DESC', array(), 0, 10);

if (!empty($face2face_by_course)) {
    echo "<h3>Top 10 des cours avec activités face2face:</h3>";
    echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
    echo "<tr><th>Course ID</th><th>Nom du cours</th><th>Nb activités</th></tr>";
    
    foreach ($face2face_by_course as $course) {
        echo "<tr>";
        echo "<td>" . $course->id . "</td>";
        echo "<td>" . htmlspecialchars($course->fullname) . "</td>";
        echo "<td>" . $course->nb_activities . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// 4. Test d'un planning spécifique
if (!empty($next_plannings)) {
    $test_planning = reset($next_plannings);
    echo "<h2>🧪 Test d'un planning spécifique (ID: " . $test_planning->planningid . ")</h2>";
    
    // Récupérer le cours de la section
    $course_id = $DB->get_field('course_sections', 'course', array('id' => $test_planning->sectionid));
    echo "<p><strong>Section ID:</strong> " . $test_planning->sectionid . "</p>";
    echo "<p><strong>Course ID de la section:</strong> " . $course_id . "</p>";
    
    if ($course_id) {
        // Chercher les activités face2face dans ce cours
        $face2face_activities = $DB->get_records_sql('
            SELECT cm.id, cm.section, cs.name as section_name, f.name as activity_name
            FROM {course_modules} cm
            JOIN {modules} m ON m.id = cm.module
            JOIN {face2face} f ON f.id = cm.instance
            JOIN {course_sections} cs ON cs.id = cm.section
            WHERE m.name = "face2face" AND cs.course = ?
            ORDER BY cm.section, cm.id', array($course_id));
        
        echo "<p><strong>Activités face2face trouvées:</strong> " . count($face2face_activities) . "</p>";
        
        if (!empty($face2face_activities)) {
            echo "<table border='1' style='border-collapse: collapse; width: 100%;'>";
            echo "<tr><th>CM ID</th><th>Section</th><th>Nom section</th><th>Nom activité</th></tr>";
            
            foreach ($face2face_activities as $activity) {
                echo "<tr>";
                echo "<td>" . $activity->id . "</td>";
                echo "<td>" . $activity->section . "</td>";
                echo "<td>" . htmlspecialchars($activity->section_name) . "</td>";
                echo "<td>" . htmlspecialchars($activity->activity_name) . "</td>";
                echo "</tr>";
            }
            echo "</table>";
        }
        
        // Chercher les utilisateurs du groupe
        $group_users = $DB->get_records_sql('
            SELECT u.id, u.firstname, u.lastname
            FROM {user} u
            JOIN {groups_members} gm ON gm.userid = u.id
            WHERE gm.groupid = ?
            ORDER BY u.lastname, u.firstname', array($test_planning->groupid), 0, 5);
        
        echo "<p><strong>Utilisateurs du groupe (5 premiers):</strong> " . count($group_users) . "</p>";
        
        if (!empty($group_users)) {
            echo "<ul>";
            foreach ($group_users as $user) {
                echo "<li>" . htmlspecialchars($user->firstname . ' ' . $user->lastname) . " (ID: " . $user->id . ")</li>";
            }
            echo "</ul>";
        }
    }
}

// 5. Vérifier la configuration
echo "<h2>⚙️ Configuration</h2>";
echo "<p><strong>completion_last_planning_id:</strong> " . get_config('theme_remui', 'completion_last_planning_id') . "</p>";

// 6. Actions de test
echo "<h2>🔧 Actions de test</h2>";
echo "<p><a href='?action=reset_config' onclick='return confirm(\"Êtes-vous sûr de vouloir réinitialiser la configuration ?\")'>🔄 Réinitialiser la configuration</a></p>";
echo "<p><a href='treat_completion.php?action=show_stats'>📊 Retour au traitement principal</a></p>";

// Traitement des actions
$action = isset($_GET['action']) ? $_GET['action'] : '';

if ($action === 'reset_config') {
    set_config('completion_last_planning_id', 0, 'theme_remui');
    echo "<div style='background: #d4edda; padding: 10px; border: 1px solid #c3e6cb; border-radius: 5px; margin: 10px 0;'>";
    echo "✅ Configuration réinitialisée !";
    echo "</div>";
    echo "<script>setTimeout(function() { window.location.href = window.location.pathname; }, 2000);</script>";
}

echo "</body></html>";

?>
