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

// Récupération de tous les responsables pédagogiques avec leurs cours, sessions et groupes associés
$query = 'SELECT srl.id as linkid, srl.courseid, srl.userid, srl.sessionid, srl.groupid,
                 u.firstname, u.lastname, u.email,
                 c.fullname as coursename, c.shortname as courseshortname,
                 s.codeligue, s.location as session_location, s.startdate, s.enddate,
                 g.name as groupname, g.description as groupdescription
          FROM mdl_smartch_respo_link srl
          JOIN mdl_user u ON u.id = srl.userid
          JOIN mdl_course c ON c.id = srl.courseid
          LEFT JOIN mdl_smartch_session s ON s.id = srl.sessionid
          LEFT JOIN mdl_groups g ON g.id = srl.groupid
          ORDER BY u.lastname, u.firstname, c.fullname, s.codeligue';

$responsables = $DB->get_records_sql($query);

// Statistiques
$total_links = count($responsables);
$unique_responsables = $DB->get_records_sql('SELECT DISTINCT userid FROM mdl_smartch_respo_link');
$unique_courses = $DB->get_records_sql('SELECT DISTINCT courseid FROM mdl_smartch_respo_link');
$unique_sessions = $DB->get_records_sql('SELECT DISTINCT sessionid FROM mdl_smartch_respo_link WHERE sessionid IS NOT NULL');
$unique_groups = $DB->get_records_sql('SELECT DISTINCT groupid FROM mdl_smartch_respo_link WHERE groupid IS NOT NULL');

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
            max-width: 1400px;
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
            font-size: 13px;
            overflow-x: auto;
            display: block;
            white-space: nowrap;
        }
        table thead,
        table tbody {
            display: table;
            width: 100%;
            table-layout: fixed;
        }
        th, td {
            padding: 8px 6px;
            text-align: left;
            border-bottom: 1px solid #ddd;
            vertical-align: top;
            word-wrap: break-word;
            max-width: 120px;
        }
        th {
            background-color: #f5f5f5;
            font-weight: bold;
            color: #333;
            position: sticky;
            top: 0;
            z-index: 10;
        }
        tr:hover {
            background-color: #f9f9f9;
        }
        .table-wrapper {
            overflow-x: auto;
            margin-top: 20px;
        }
        th:nth-child(1), td:nth-child(1) { min-width: 60px; } /* ID Lien */
        th:nth-child(2), td:nth-child(2) { min-width: 120px; } /* Responsable */
        th:nth-child(3), td:nth-child(3) { min-width: 150px; } /* Email */
        th:nth-child(4), td:nth-child(4) { min-width: 180px; } /* Cours */
        th:nth-child(5), td:nth-child(5) { min-width: 100px; } /* Code Cours */
        th:nth-child(6), td:nth-child(6) { min-width: 150px; } /* Session */
        th:nth-child(7), td:nth-child(7) { min-width: 100px; } /* Code Ligue */
        th:nth-child(8), td:nth-child(8) { min-width: 120px; } /* Groupe */
        th:nth-child(9), td:nth-child(9) { min-width: 80px; } /* ID Cours */
        th:nth-child(10), td:nth-child(10) { min-width: 80px; } /* ID Session */
        th:nth-child(11), td:nth-child(11) { min-width: 80px; } /* ID Groupe */
        th:nth-child(12), td:nth-child(12) { min-width: 80px; } /* ID Utilisateur */
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
            <div class="stat-item">
                <div class="stat-number"><?php echo count($unique_sessions); ?></div>
                <div class="stat-label">Sessions liées</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo count($unique_groups); ?></div>
                <div class="stat-label">Groupes liés</div>
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
            <div class="table-wrapper">
                <table>
                <thead>
                    <tr>
                        <th>ID Lien</th>
                        <th>Responsable</th>
                        <th>Email</th>
                        <th>Cours</th>
                        <th>Code Cours</th>
                        <th>Session</th>
                        <th>Code Ligue</th>
                        <th>Groupe</th>
                        <th>ID Cours</th>
                        <th>ID Session</th>
                        <th>ID Groupe</th>
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
                            <td>
                                <?php if ($resp->session_location): ?>
                                    <?php echo htmlspecialchars($resp->session_location); ?>
                                    <?php if ($resp->startdate && $resp->enddate): ?>
                                        <br><small style="color: #666;">
                                            <?php echo date('d/m/Y', strtotime($resp->startdate)); ?> - 
                                            <?php echo date('d/m/Y', strtotime($resp->enddate)); ?>
                                        </small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #999; font-style: italic;">Non définie</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($resp->codeligue): ?>
                                    <?php echo htmlspecialchars($resp->codeligue); ?>
                                <?php else: ?>
                                    <span style="color: #999; font-style: italic;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($resp->groupname): ?>
                                    <?php echo htmlspecialchars($resp->groupname); ?>
                                    <?php if ($resp->groupdescription): ?>
                                        <br><small style="color: #666;"><?php echo htmlspecialchars($resp->groupdescription); ?></small>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="color: #999; font-style: italic;">Non défini</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $resp->courseid; ?></td>
                            <td>
                                <?php if ($resp->sessionid): ?>
                                    <?php echo $resp->sessionid; ?>
                                <?php else: ?>
                                    <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($resp->groupid): ?>
                                    <?php echo $resp->groupid; ?>
                                <?php else: ?>
                                    <span style="color: #999;">-</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo $resp->userid; ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                </table>
            </div>
        <?php endif; ?>

        <!-- Informations techniques -->
        <div style="margin-top: 40px; padding-top: 20px; border-top: 1px solid #ddd; color: #666; font-size: 12px;">
            <p><strong>Table principale :</strong> mdl_smartch_respo_link</p>
            <p><strong>Tables liées :</strong> mdl_user, mdl_course, mdl_smartch_session, mdl_groups</p>
            <p><strong>Champs incluant :</strong> courseid, userid, sessionid, groupid</p>
            <p><strong>Dernière mise à jour :</strong> <?php echo userdate(time(), get_string('strftimedatetimeshort')); ?></p>
            <p><strong>Version :</strong> Avec support des sessions et groupes</p>
        </div>
    </div>
</body>
</html>
