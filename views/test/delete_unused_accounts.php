<?php
/**
 * Script pour afficher et supprimer les comptes utilisateurs qui ne se sont jamais connectés
 * 
 * Ce script respecte les meilleures pratiques Moodle :
 * - Utilise delete_user() pour une suppression propre
 * - Vérifie les permissions admin
 * - Protège les comptes système (admin, guest)
 * - Inclut une confirmation avant suppression
 * - Traitement par lots pour gérer de grands volumes
 * 
 * @package    theme_remui
 * @copyright  2025
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/user/lib.php');
require_once($CFG->libdir . '/adminlib.php');

// Vérification de la connexion et des permissions
require_login();
require_capability('moodle/user:delete', context_system::instance());

// Vérifier que l'utilisateur est un administrateur du site
if (!is_siteadmin()) {
    print_error('nopermissions', 'error', '', 'Supprimer des utilisateurs');
}

// Configuration de la page
$PAGE->set_url(new moodle_url('/theme/remui/views/test/delete_unused_accounts.php'));
$PAGE->set_context(context_system::instance());
$PAGE->set_title(get_string('administration'));
$PAGE->set_heading('Gestion des comptes non utilisés');

// Traitement de la suppression
$action = optional_param('action', '', PARAM_ALPHA);
$confirm = optional_param('confirm', 0, PARAM_INT);
$datebefore = optional_param('datebefore', '', PARAM_TEXT); // Date limite (avant cette date)
$batchsize = 50; // Nombre d'utilisateurs à traiter par lot

// Traitement AJAX pour suppression par lots
if ($action === 'deletebatch' && confirm_sesskey()) {
    // Désactiver le timeout et les notifications
    @set_time_limit(300); // 5 minutes max par lot
    
    $offset = optional_param('offset', 0, PARAM_INT);
    $limit = optional_param('limit', $batchsize, PARAM_INT);
    
    // Construire la condition de date
    $datecondition = '';
    $params = [
        'guestid' => $CFG->siteguest,
        'adminid' => get_admin()->id
    ];
    
    if (!empty($datebefore)) {
        $timestamp = strtotime($datebefore . ' 00:00:00');
        if ($timestamp !== false) {
            $datecondition = 'AND u.timecreated >= :dateafter';
            $params['dateafter'] = $timestamp;
        }
    }
    
    // Requête pour obtenir un lot d'utilisateurs
    $sql = "SELECT u.id
            FROM {user} u
            WHERE u.lastaccess = 0
            AND u.deleted = 0
            AND u.confirmed = 1
            AND u.id != :guestid
            AND u.id != :adminid
            AND u.username != 'guest'
            $datecondition
            ORDER BY u.id ASC";
    
    // Récupérer seulement les IDs pour ce lot
    $alluserids = $DB->get_fieldset_sql($sql, $params);
    $batchids = array_slice($alluserids, $offset, $limit);
    
    $deletedcount = 0;
    $errors = [];
    
    // Supprimer chaque utilisateur du lot
    foreach ($batchids as $userid) {
        try {
            // Récupérer l'objet utilisateur complet
            $user = $DB->get_record('user', ['id' => $userid]);
            
            if (!$user) {
                $errors[] = "Utilisateur introuvable: ID {$userid}";
                continue;
            }
            
            // Utilisation de la fonction officielle Moodle
            if (delete_user($user)) {
                $deletedcount++;
            } else {
                $errors[] = "Erreur lors de la suppression: {$user->username}";
            }
        } catch (Exception $e) {
            $errors[] = "Exception ID {$userid}: " . $e->getMessage();
        }
    }
    
    // Compter le nombre restant
    $remaining = count($alluserids) - ($offset + $deletedcount);
    if ($remaining < 0) {
        $remaining = 0;
    }
    
    // Retourner une réponse JSON
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'deleted' => $deletedcount,
        'remaining' => $remaining,
        'total' => count($alluserids),
        'errors' => $errors,
        'offset' => $offset + $limit
    ]);
    exit;
}

// Compte total pour l'affichage (action AJAX)
if ($action === 'getcount' && confirm_sesskey()) {
    // Construire la condition de date
    $datecondition = '';
    $params = [
        'guestid' => $CFG->siteguest,
        'adminid' => get_admin()->id
    ];
    
    if (!empty($datebefore)) {
        $timestamp = strtotime($datebefore . ' 00:00:00');
        if ($timestamp !== false) {
            $datecondition = 'AND u.timecreated >= :dateafter';
            $params['dateafter'] = $timestamp;
        }
    }
    
    $sql = "SELECT COUNT(u.id)
            FROM {user} u
            WHERE u.lastaccess = 0
            AND u.deleted = 0
            AND u.confirmed = 1
            AND u.id != :guestid
            AND u.id != :adminid
            AND u.username != 'guest'
            $datecondition";
    
    $count = $DB->count_records_sql($sql, $params);
    
    header('Content-Type: application/json');
    echo json_encode(['count' => $count]);
    exit;
}

// Affichage de la page
echo $OUTPUT->header();

// Construire la condition de date pour l'affichage
$datecondition = '';
$params = [
    'guestid' => $CFG->siteguest,
    'adminid' => get_admin()->id
];

// Récupérer la date du formulaire si présente
$filterdate = optional_param('filterdate', '', PARAM_TEXT);
$datetimestamp = 0;

if (!empty($filterdate)) {
    $datetimestamp = strtotime($filterdate . ' 23:59:59');
    if ($datetimestamp !== false) {
        $datecondition = 'AND u.timecreated < :datebefore';
        $params['datebefore'] = $datetimestamp;
    }
}

// Compter d'abord le total (pour le compteur et la suppression)
$sqlcount = "SELECT COUNT(u.id)
             FROM {user} u
             WHERE u.lastaccess = 0
             AND u.deleted = 0
             AND u.confirmed = 1
             AND u.id != :guestid
             AND u.id != :adminid
             AND u.username != 'guest'
             $datecondition";

$totalcount = $DB->count_records_sql($sqlcount, $params);

// Requête pour obtenir SEULEMENT les 100 premiers utilisateurs (pour l'affichage)
$displayLimit = 100;
$sql = "SELECT u.id, u.username, u.firstname, u.lastname, u.email, u.timecreated, u.suspended
        FROM {user} u
        WHERE u.lastaccess = 0
        AND u.deleted = 0
        AND u.confirmed = 1
        AND u.id != :guestid
        AND u.id != :adminid
        AND u.username != 'guest'
        $datecondition
        ORDER BY u.timecreated DESC";

$unusedusers = $DB->get_records_sql($sql, $params, 0, $displayLimit);
$displaycount = count($unusedusers);

?>

<style>
    .unused-accounts-container {
        padding: 20px;
        max-width: 1400px;
        margin: 0 auto;
    }
    
    .summary-box {
        background: #f9f9f9;
        border-left: 4px solid #d9534f;
        padding: 15px 20px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
    
    .summary-box h3 {
        margin: 0 0 10px 0;
        color: #d9534f;
    }
    
    .warning-box {
        background: #fff3cd;
        border: 1px solid #ffc107;
        padding: 15px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
    
    .warning-box strong {
        color: #856404;
    }
    
    .users-table {
        width: 100%;
        border-collapse: collapse;
        background: white;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        margin-bottom: 20px;
    }
    
    .users-table thead {
        background: #f8f9fa;
    }
    
    .users-table th {
        padding: 12px;
        text-align: left;
        font-weight: bold;
        border-bottom: 2px solid #dee2e6;
        color: #495057;
    }
    
    .users-table td {
        padding: 10px 12px;
        border-bottom: 1px solid #dee2e6;
    }
    
    .users-table tbody tr:hover {
        background: #f8f9fa;
    }
    
    .user-suspended {
        color: #dc3545;
        font-weight: bold;
    }
    
    .btn-delete-all {
        background: #d9534f;
        color: white;
        padding: 12px 24px;
        border: none;
        border-radius: 4px;
        font-size: 16px;
        cursor: pointer;
        transition: background 0.3s;
    }
    
    .btn-delete-all:hover {
        background: #c9302c;
    }
    
    .btn-delete-all:disabled {
        background: #ccc;
        cursor: not-allowed;
    }
    
    .action-buttons {
        margin: 20px 0;
        display: flex;
        gap: 10px;
        align-items: center;
        flex-wrap: wrap;
    }
    
    .badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 12px;
        font-weight: bold;
    }
    
    .badge-suspended {
        background: #dc3545;
        color: white;
    }
    
    .badge-active {
        background: #28a745;
        color: white;
    }
    
    /* Barre de progression */
    .progress-container {
        display: none;
        margin: 20px 0;
        padding: 20px;
        background: #f8f9fa;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
    
    .progress-container.active {
        display: block;
    }
    
    .progress-bar-wrapper {
        width: 100%;
        height: 30px;
        background: #e9ecef;
        border-radius: 15px;
        overflow: hidden;
        margin: 10px 0;
        position: relative;
    }
    
    .progress-bar {
        height: 100%;
        background: linear-gradient(90deg, #28a745, #20c997);
        transition: width 0.3s ease;
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: bold;
        font-size: 14px;
    }
    
    .progress-info {
        display: flex;
        justify-content: space-between;
        margin-top: 10px;
        font-size: 14px;
    }
    
    .progress-status {
        font-weight: bold;
        color: #28a745;
        margin: 10px 0;
    }
    
    .progress-errors {
        max-height: 200px;
        overflow-y: auto;
        background: #fff3cd;
        padding: 10px;
        border-radius: 4px;
        margin-top: 10px;
        font-size: 12px;
    }
    
    .progress-errors ul {
        margin: 0;
        padding-left: 20px;
    }
    
    .btn-cancel {
        background: #6c757d;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        margin-left: 10px;
    }
    
    .btn-cancel:hover {
        background: #5a6268;
    }
    
    /* Filtre de date */
    .filter-box {
        background: #fff;
        border: 2px solid #007bff;
        padding: 20px;
        margin-bottom: 20px;
        border-radius: 8px;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    
    .filter-box h3 {
        margin: 0 0 15px 0;
        color: #007bff;
    }
    
    .filter-form {
        display: flex;
        gap: 15px;
        align-items: flex-end;
        flex-wrap: wrap;
    }
    
    .form-group {
        display: flex;
        flex-direction: column;
        gap: 5px;
    }
    
    .form-group label {
        font-weight: bold;
        font-size: 14px;
    }
    
    .form-group input[type="date"] {
        padding: 8px 12px;
        border: 1px solid #ced4da;
        border-radius: 4px;
        font-size: 14px;
        min-width: 200px;
    }
    
    .btn-filter {
        background: #007bff;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        height: 38px;
    }
    
    .btn-filter:hover {
        background: #0056b3;
    }
    
    .btn-reset {
        background: #6c757d;
        color: white;
        padding: 10px 20px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 14px;
        height: 38px;
    }
    
    .btn-reset:hover {
        background: #5a6268;
    }
    
    .filter-active {
        display: inline-block;
        margin-left: 10px;
        padding: 5px 10px;
        background: #007bff;
        color: white;
        border-radius: 4px;
        font-size: 12px;
        font-weight: bold;
    }
    
    .info-box {
        background: #d1ecf1;
        border-left: 4px solid #17a2b8;
        padding: 15px 20px;
        margin-bottom: 20px;
        border-radius: 4px;
    }
    
    .info-box strong {
        color: #0c5460;
    }
</style>

<div class="unused-accounts-container">
    <h2>🗑️ Gestion des comptes non utilisés</h2>
    
    <!-- Filtre par date -->
    <div class="filter-box">
        <h3>🔍 Filtrer les comptes</h3>
        <form method="get" class="filter-form" id="filterForm">
            <div class="form-group">
                <label for="filterdate">Supprimer uniquement les comptes créés après :</label>
                <input type="date" 
                       id="filterdate" 
                       name="filterdate" 
                       value="<?php echo htmlspecialchars($filterdate); ?>"
                       max="<?php echo date('Y-m-d'); ?>">
            </div>
            <button type="submit" class="btn-filter">📅 Appliquer le filtre</button>
            <?php if (!empty($filterdate)): ?>
                <a href="<?php echo $PAGE->url; ?>" class="btn-reset">🔄 Réinitialiser</a>
            <?php endif; ?>
        </form>
        <?php if (!empty($filterdate)): ?>
            <p style="margin-top: 10px; margin-bottom: 0;">
                <span class="filter-active">✓ Filtre actif : Depuis le <?php echo date('d/m/Y', $datetimestamp); ?></span>
            </p>
        <?php endif; ?>
    </div>
    
    <div class="summary-box">
        <h3>📊 Résumé</h3>
        <p><strong>Total des comptes jamais connectés<?php echo !empty($filterdate) ? ' (avec filtre)' : ''; ?> :</strong> <?php echo $totalcount; ?> compte(s)</p>
        <p>Ces utilisateurs ont été créés<?php echo !empty($filterdate) ? ' <strong>à partir du ' . date('d/m/Y', $datetimestamp) . '</strong>' : ''; ?> mais ne se sont <strong>JAMAIS</strong> connectés à la plateforme.</p>
        <?php if ($totalcount > $displayLimit): ?>
        <p style="margin-top: 10px; font-size: 14px; color: #17a2b8;">
            ⚡ <em>Le tableau ci-dessous affiche les <?php echo $displayLimit; ?> premiers comptes pour optimiser le temps de chargement. 
            La suppression s'appliquera bien aux <?php echo $totalcount; ?> comptes.</em>
        </p>
        <?php endif; ?>
    </div>
    
    <?php if ($totalcount > 0): ?>
        <div class="warning-box">
            <strong>⚠️ AVERTISSEMENT :</strong>
            <ul style="margin: 10px 0 0 0;">
                <li>La suppression est <strong>IRRÉVERSIBLE</strong></li>
                <li>Les comptes administrateur et invité sont automatiquement protégés</li>
                <?php if (!empty($filterdate)): ?>
                <li><strong>Filtre actif :</strong> Seuls les comptes créés <strong>à partir du <?php echo date('d/m/Y', $datetimestamp); ?></strong> seront supprimés</li>
                <?php endif; ?>
                <li>Il est <strong>FORTEMENT RECOMMANDÉ</strong> de faire une sauvegarde de la base de données avant cette opération</li>
                <li>Cette action utilise la fonction officielle <code>delete_user()</code> de Moodle</li>
                <li>Les suppressions sont enregistrées dans les logs du système</li>
                <li><strong>Traitement par lots :</strong> La suppression se fait progressivement pour éviter les timeouts</li>
            </ul>
        </div>
        
        <div class="action-buttons">
            <button id="btnDeleteAll" class="btn-delete-all" onclick="startBatchDeletion();">
                <?php if (!empty($filterdate)): ?>
                    🗑️ Supprimer les comptes filtrés (<?php echo $totalcount; ?>)
                <?php else: ?>
                    🗑️ Supprimer TOUS les comptes non utilisés (<?php echo $totalcount; ?>)
                <?php endif; ?>
            </button>
            <?php if (!empty($filterdate)): ?>
            <div style="font-size: 13px; color: #007bff; margin-left: 10px;">
                📌 Seuls les <strong><?php echo $totalcount; ?> comptes</strong> créés à partir du <strong><?php echo date('d/m/Y', $datetimestamp); ?></strong> seront supprimés
            </div>
            <?php endif; ?>
        </div>
        
        <!-- Barre de progression -->
        <div id="progressContainer" class="progress-container">
            <h3>⏳ Suppression en cours...</h3>
            <div class="progress-bar-wrapper">
                <div id="progressBar" class="progress-bar" style="width: 0%;">0%</div>
            </div>
            <div class="progress-info">
                <div>
                    <strong>Traités :</strong> <span id="processedCount">0</span> / <span id="totalCount">0</span>
                </div>
                <div>
                    <strong>Supprimés :</strong> <span id="deletedCount">0</span>
                </div>
                <div>
                    <strong>Restants :</strong> <span id="remainingCount">0</span>
                </div>
            </div>
            <div class="progress-status" id="progressStatus">Initialisation...</div>
            <button id="btnCancel" class="btn-cancel" onclick="cancelDeletion();">⏸️ Annuler</button>
            <div id="progressErrors" class="progress-errors" style="display: none;">
                <strong>⚠️ Erreurs rencontrées :</strong>
                <ul id="errorsList"></ul>
            </div>
        </div>
        
        <h3>📋 Liste des comptes jamais connectés</h3>
        
        <?php if ($totalcount > $displayLimit): ?>
        <div class="info-box">
            <strong>ℹ️ Information :</strong> Pour des raisons de performance, seuls les <strong><?php echo $displayLimit; ?> premiers comptes</strong> sont affichés ci-dessous 
            (triés par date de création, du plus récent au plus ancien).
            <br>
            <strong>IMPORTANT :</strong> Le bouton de suppression supprimera bien <strong>TOUS les <?php echo $totalcount; ?> comptes</strong>, pas seulement ceux affichés.
        </div>
        <?php endif; ?>
        
        <div style="overflow-x: auto;">
            <table class="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nom d'utilisateur</th>
                        <th>Prénom</th>
                        <th>Nom</th>
                        <th>Email</th>
                        <th>Date de création</th>
                        <th>Statut</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($unusedusers as $user): ?>
                    <tr>
                        <td><?php echo $user->id; ?></td>
                        <td><strong><?php echo s($user->username); ?></strong></td>
                        <td><?php echo s($user->firstname); ?></td>
                        <td><?php echo s($user->lastname); ?></td>
                        <td><?php echo s($user->email); ?></td>
                        <td><?php echo userdate($user->timecreated, '%d/%m/%Y %H:%M'); ?></td>
                        <td>
                            <?php if ($user->suspended): ?>
                                <span class="badge badge-suspended">Suspendu</span>
                            <?php else: ?>
                                <span class="badge badge-active">Actif</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        
        <?php if ($totalcount > $displayLimit): ?>
        <div class="info-box" style="margin-top: 15px;">
            <strong>📊 Affichage :</strong> <?php echo $displaycount; ?> comptes affichés sur un total de <strong><?php echo $totalcount; ?> comptes</strong> correspondant aux critères.
            <?php if ($totalcount - $displaycount > 0): ?>
            <br><strong><?php echo ($totalcount - $displaycount); ?> autres comptes</strong> non affichés seront également supprimés lors de la suppression.
            <?php endif; ?>
        </div>
        <?php endif; ?>
        
        <div class="action-buttons">
            <button class="btn-delete-all" onclick="startBatchDeletion();">
                <?php if (!empty($filterdate)): ?>
                    🗑️ Supprimer les comptes filtrés (<?php echo $totalcount; ?>)
                <?php else: ?>
                    🗑️ Supprimer TOUS les comptes non utilisés (<?php echo $totalcount; ?>)
                <?php endif; ?>
            </button>
            <?php if (!empty($filterdate)): ?>
            <div style="font-size: 13px; color: #007bff; margin-left: 10px;">
                📌 Seuls les <strong><?php echo $totalcount; ?> comptes</strong> créés à partir du <strong><?php echo date('d/m/Y', $datetimestamp); ?></strong> seront supprimés
            </div>
            <?php endif; ?>
        </div>
        
    <?php else: ?>
        <div class="summary-box" style="border-left-color: #28a745;">
            <h3 style="color: #28a745;">✅ Aucun compte à supprimer</h3>
            <?php if (!empty($filterdate)): ?>
                <p>Aucun compte trouvé avec les critères de filtre actuels (créés à partir du <?php echo date('d/m/Y', $datetimestamp); ?>).</p>
                <p>💡 <strong>Suggestion :</strong> Essayez de modifier la date du filtre ou de le réinitialiser pour voir tous les comptes non utilisés.</p>
                <p><a href="<?php echo $PAGE->url; ?>" class="btn-reset" style="display: inline-block; text-decoration: none;">🔄 Réinitialiser le filtre</a></p>
            <?php else: ?>
                <p>Tous les comptes confirmés ont été utilisés au moins une fois, ou il n'y a aucun compte non utilisé actuellement.</p>
                <p>🎉 Félicitations ! Votre plateforme n'a aucun compte inactif qui n'a jamais été utilisé.</p>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    
    <div style="margin-top: 30px; padding: 15px; background: #e9ecef; border-radius: 4px;">
        <h4>ℹ️ Informations techniques</h4>
        <ul>
            <li><strong>Critères de sélection :</strong>
                <ul>
                    <li>lastaccess = 0 (jamais connecté)</li>
                    <li>deleted = 0 (non supprimé)</li>
                    <li>confirmed = 1 (compte confirmé)</li>
                    <li>Exclusion des comptes guest et admin</li>
                    <?php if (!empty($filterdate)): ?>
                    <li><strong>timecreated >= <?php echo date('d/m/Y', $datetimestamp); ?></strong> (filtre de date actif)</li>
                    <?php endif; ?>
                </ul>
            </li>
            <li><strong>Filtre par date :</strong> Permet de ne supprimer que les comptes créés après une date spécifique (utilise le champ timecreated)</li>
            <li><strong>Affichage optimisé :</strong> Limite l'affichage du tableau à <?php echo $displayLimit; ?> résultats maximum pour améliorer les performances de chargement de la page</li>
            <li><strong>Méthode de suppression :</strong> Utilise la fonction <code>delete_user()</code> de Moodle</li>
            <li><strong>Traitement par lots :</strong> Suppression par lots de <?php echo $batchsize; ?> utilisateurs (optimisé pour les gros volumes)</li>
            <li><strong>Technologie :</strong> AJAX avec barre de progression en temps réel</li>
            <li><strong>Sécurité :</strong> 
                <ul>
                    <li>Possibilité d'annuler en cours de traitement</li>
                    <li>Timeout de 5 minutes par lot</li>
                    <li>Gestion des erreurs avec rapport détaillé</li>
                </ul>
            </li>
            <li><strong>Logging :</strong> Toutes les suppressions sont enregistrées dans les logs système</li>
            <li><strong>Protection CSRF :</strong> Utilise le sesskey de Moodle pour sécuriser les requêtes</li>
            <li><strong>Performance :</strong> Pause de 100ms entre chaque lot pour éviter la surcharge serveur</li>
        </ul>
    </div>
</div>

<script>
// Variables globales pour le traitement par lots
let deletionInProgress = false;
let deletionCancelled = false;
let totalUsers = 0;
let processedUsers = 0;
let deletedUsers = 0;
let allErrors = [];
const batchSize = 50; // Nombre d'utilisateurs par lot
const sesskey = '<?php echo sesskey(); ?>';
const filterDate = '<?php echo htmlspecialchars($filterdate); ?>'; // Date de filtre

function startBatchDeletion() {
    const count = <?php echo $totalcount; ?>;
    
    let dateMsg = '';
    if (filterDate) {
        dateMsg = `\n\nFiltre actif : Comptes créés à partir du <?php echo !empty($filterdate) ? date('d/m/Y', $datetimestamp) : ''; ?>\n`;
    }
    
    const message = `⚠️ CONFIRMATION REQUISE ⚠️\n\n` +
                    `Vous êtes sur le point de supprimer ${count} compte(s) qui ne se sont JAMAIS connectés.${dateMsg}\n` +
                    `Cette action est IRRÉVERSIBLE.\n\n` +
                    `Avez-vous fait une sauvegarde de la base de données ?\n\n` +
                    `Traitement par lots : ${batchSize} comptes à la fois\n\n` +
                    `Êtes-vous sûr de vouloir continuer ?`;
    
    if (!confirm(message)) {
        return false;
    }
    
    // Double confirmation pour plus de sécurité
    const doubleConfirm = confirm(`DERNIÈRE CONFIRMATION\n\nVous allez supprimer ${count} compte(s).\n\nCliquez sur OK pour confirmer définitivement.`);
    
    if (!doubleConfirm) {
        return false;
    }
    
    // Initialiser la suppression
    deletionInProgress = true;
    deletionCancelled = false;
    totalUsers = count;
    processedUsers = 0;
    deletedUsers = 0;
    allErrors = [];
    
    // Afficher la barre de progression
    document.getElementById('progressContainer').classList.add('active');
    document.getElementById('btnDeleteAll').disabled = true;
    document.getElementById('totalCount').textContent = totalUsers;
    document.getElementById('remainingCount').textContent = totalUsers;
    
    // Cacher le tableau pour améliorer les performances
    document.querySelector('.users-table').parentElement.style.display = 'none';
    
    // Démarrer le traitement
    processBatch(0);
}

function processBatch(offset) {
    if (deletionCancelled) {
        finishDeletion(true);
        return;
    }
    
    if (!deletionInProgress) {
        return;
    }
    
    updateStatus(`Traitement du lot ${Math.floor(offset / batchSize) + 1}... (${offset} à ${offset + batchSize})`);
    
    // Appel AJAX pour supprimer un lot
    let url = window.location.pathname + 
              '?action=deletebatch' +
              '&sesskey=' + sesskey +
              '&offset=' + offset +
              '&limit=' + batchSize;
    
    // Ajouter le filtre de date si présent
    if (filterDate) {
        url += '&datebefore=' + encodeURIComponent(filterDate);
    }
    
    fetch(url)
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Mettre à jour les compteurs
                processedUsers += data.deleted;
                deletedUsers += data.deleted;
                
                // Ajouter les erreurs
                if (data.errors && data.errors.length > 0) {
                    allErrors = allErrors.concat(data.errors);
                    displayErrors();
                }
                
                // Mettre à jour l'affichage
                updateProgress();
                
                // Si il reste des utilisateurs, continuer
                if (data.remaining > 0 && !deletionCancelled) {
                    // Attendre un peu pour éviter de surcharger le serveur
                    setTimeout(() => {
                        processBatch(data.offset);
                    }, 100);
                } else {
                    // Terminé
                    finishDeletion(false);
                }
            } else {
                updateStatus('❌ Erreur lors du traitement du lot');
                finishDeletion(true);
            }
        })
        .catch(error => {
            console.error('Erreur AJAX:', error);
            updateStatus('❌ Erreur réseau : ' + error.message);
            allErrors.push('Erreur réseau : ' + error.message);
            displayErrors();
            finishDeletion(true);
        });
}

function updateProgress() {
    const percentage = totalUsers > 0 ? Math.round((processedUsers / totalUsers) * 100) : 0;
    const remaining = Math.max(0, totalUsers - processedUsers);
    
    document.getElementById('progressBar').style.width = percentage + '%';
    document.getElementById('progressBar').textContent = percentage + '%';
    document.getElementById('processedCount').textContent = processedUsers;
    document.getElementById('deletedCount').textContent = deletedUsers;
    document.getElementById('remainingCount').textContent = remaining;
}

function updateStatus(message) {
    document.getElementById('progressStatus').textContent = message;
}

function displayErrors() {
    if (allErrors.length > 0) {
        const errorsList = document.getElementById('errorsList');
        errorsList.innerHTML = '';
        
        allErrors.forEach(error => {
            const li = document.createElement('li');
            li.textContent = error;
            errorsList.appendChild(li);
        });
        
        document.getElementById('progressErrors').style.display = 'block';
    }
}

function finishDeletion(cancelled) {
    deletionInProgress = false;
    
    if (cancelled) {
        updateStatus(`⏸️ Suppression annulée. ${deletedUsers} compte(s) supprimé(s) avant l'annulation.`);
    } else {
        updateStatus(`✅ Suppression terminée ! ${deletedUsers} compte(s) supprimé(s) avec succès.`);
        document.getElementById('progressBar').style.background = 'linear-gradient(90deg, #28a745, #20c997)';
    }
    
    document.getElementById('btnCancel').disabled = true;
    
    // Afficher un message de succès et recharger après 3 secondes
    if (!cancelled && allErrors.length === 0) {
        setTimeout(() => {
            window.location.reload();
        }, 3000);
    } else if (!cancelled && allErrors.length > 0) {
        updateStatus(`⚠️ Suppression terminée avec ${allErrors.length} erreur(s). ${deletedUsers} compte(s) supprimé(s).`);
    }
}

function cancelDeletion() {
    if (confirm('Êtes-vous sûr de vouloir annuler la suppression ?')) {
        deletionCancelled = true;
        document.getElementById('btnCancel').disabled = true;
        updateStatus('⏸️ Annulation en cours...');
    }
}
</script>

<?php
echo $OUTPUT->footer();
?>

