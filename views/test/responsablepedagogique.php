<?php

require_once(__DIR__ . '/../../../../config.php');
include $CFG->dirroot . '/theme/remui/views/utils.php';

global $USER, $DB, $CFG;

// Vérification des permissions (optionnel, à adapter selon vos besoins)
require_login();

// Gestion des actions
$action = optional_param('action', '', PARAM_TEXT);
$courseid = optional_param('courseid', 0, PARAM_INT);

// Traitement du reset (suppression de tous les liens)
if ($action === 'reset' && confirm_sesskey()) {
    $DB->delete_records('smartch_respo_link');
    redirect($CFG->wwwroot . '/theme/remui/views/test/responsablepedagogique.php', 
             'Tous les liens de responsables pédagogiques ont été supprimés.', 
             null, 
             \core\output\notification::NOTIFY_SUCCESS);
}

// Récupération de tous les responsables pédagogiques avec leurs cours associés
$query = 'SELECT srl.id as linkid, srl.courseid, srl.userid, 
                 u.firstname, u.lastname, u.email,
                 c.fullname as coursename, c.shortname as courseshortname
          FROM mdl_smartch_respo_link srl
          JOIN mdl_user u ON u.id = srl.userid
          JOIN mdl_course c ON c.id = srl.courseid
          ORDER BY u.lastname, u.firstname, c.fullname';

$responsables = $DB->get_records_sql($query);

// Statistiques
$total_links = count($responsables);
$unique_responsables = $DB->get_records_sql('SELECT DISTINCT userid FROM mdl_smartch_respo_link');
$unique_courses = $DB->get_records_sql('SELECT DISTINCT courseid FROM mdl_smartch_respo_link');

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Gestion des Responsables Pédagogiques</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            background-color: #f5f5f5;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            text-align: center;
            margin-bottom: 30px;
        }
        .stats {
            background-color: #e3f2fd;
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: flex;
            justify-content: space-around;
            text-align: center;
        }
        .stat-item {
            flex: 1;
        }
        .stat-number {
            font-size: 24px;
            font-weight: bold;
            color: #1976d2;
        }
        .stat-label {
            font-size: 14px;
            color: #666;
        }
        .actions {
            margin-bottom: 20px;
            text-align: center;
        }
        .btn {
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            font-size: 14px;
            margin: 5px;
        }
        .btn-danger {
            background-color: #d32f2f;
            color: white;
        }
        .btn-danger:hover {
            background-color: #b71c1c;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
            color: #333;
        }
        tr:hover {
            background-color: #f9f9f9;
        }
        .no-data {
            text-align: center;
            padding: 40px;
            color: #666;
            font-style: italic;
        }
        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 4px;
        }
        .alert-warning {
            background-color: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }
    </style>
    <script>
        function confirmReset() {
            return confirm('Êtes-vous sûr de vouloir supprimer TOUS les liens de responsables pédagogiques ?\n\nCette action est irréversible !');
        }
    </script>
</head>
<body>
    <div class="container">
        <h1>Gestion des Responsables Pédagogiques</h1>
        
        <!-- Statistiques -->
        <div class="stats">
            <div class="stat-item">
                <div class="stat-number"><?php echo $total_links; ?></div>
                <div class="stat-label">Liens totaux</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo count($unique_responsables); ?></div>
                <div class="stat-label">Responsables uniques</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo count($unique_courses); ?></div>
                <div class="stat-label">Cours associés</div>
            </div>
        </div>

        <!-- Actions -->
        <div class="actions">
            <?php if ($total_links > 0): ?>
                <div class="alert alert-warning">
                    <strong>Attention :</strong> Le bouton "Reset" supprimera définitivement tous les liens entre responsables et cours.
                </div>
                <form method="post" style="display: inline;" onsubmit="return confirmReset();">
                    <input type="hidden" name="action" value="reset">
                    <input type="hidden" name="sesskey" value="<?php echo sesskey(); ?>">
                    <button type="submit" class="btn btn-danger">
                        🗑️ Reset - Supprimer tous les liens
                    </button>
                </form>
            <?php endif; ?>
        </div>

        <!-- Tableau des responsables -->
        <?php if (empty($responsables)): ?>
            <div class="no-data">
                <h3>Aucun responsable pédagogique trouvé</h3>
                <p>Il n'y a actuellement aucun lien dans la table mdl_smartch_respo_link.</p>
            </div>
        <?php else: ?>
            <table>
                <thead>
                    <tr>
                        <th>ID Lien</th>
                        <th>Responsable</th>
                        <th>Email</th>
                        <th>Cours</th>
                        <th>Code Cours</th>
                        <th>ID Cours</th>
                        <th>ID Utilisateur</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($responsables as $resp): ?>
                        <tr>
                            <td><?php echo $resp->linkid; ?></td>
                            <td><?php echo htmlspecialchars($resp->firstname . ' ' . $resp->lastname); ?></td>
                            <td><?php echo htmlspecialchars($resp->email); ?></td>
                            <td><?php echo htmlspecialchars($resp->coursename); ?></td>
                            <td><?php echo htmlspecialchars($resp->courseshortname); ?></td>
                            <td><?php echo $resp->courseid; ?></td>
                            <td><?php echo $resp->userid; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Informations techniques -->
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px;">
            <p><strong>Table utilisée :</strong> mdl_smartch_respo_link</p>
            <p><strong>Dernière mise à jour :</strong> <?php echo userdate(time(), get_string('strftimedatetimeshort')); ?></p>
        </div>
    </div>
</body>
</html>
