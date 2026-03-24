<?php

namespace Dompdf;

require_once(__DIR__ . '/../../../config.php');

include $CFG->dirroot . '/theme/remui/views/utils.php';

set_time_limit(300);

global $USER, $DB, $CFG;

$content =  '';

$groupid = $_GET['groupid'];

//on va chercher le cours
$querycourse = 'SELECT c.*
FROM mdl_groups g
JOIN mdl_course c ON c.id = g.courseid
WHERE g.id = ' . $groupid;

$courseresult = $DB->get_records_sql($querycourse, null);

$course = reset($courseresult);

$content .= '<h1>' . $course->fullname . '</h1>';

$session = $DB->get_record('smartch_session', ['groupid' => $groupid]);

if ($session) {
  $content .= '<div>Session du ' . userdate($session->startdate, get_string('strftimedate')) . ' au ' . userdate($session->enddate, get_string('strftimedate') . '</div>');
}

$content .= '<div style="margin:10px 0;">Extraction du rapport le ' . userdate(Time(), get_string('strftimedate')) . '</div>';


//on va chercher les membres du groupe
$querygroupmembers = 'SELECT DISTINCT u.id, u.username, u.firstname, u.lastname, u.email, r.shortname, r.id as roleid, r.shortname as rolename
FROM mdl_role_assignments AS ra 
LEFT JOIN mdl_user_enrolments AS ue ON ra.userid = ue.userid
LEFT JOIN mdl_role AS r ON ra.roleid = r.id 
LEFT JOIN mdl_context AS c ON c.id = ra.contextid 
LEFT JOIN mdl_enrol AS e ON e.courseid = c.instanceid AND ue.enrolid = e.id 
LEFT JOIN mdl_user u ON u.id = ue.userid
LEFT JOIN mdl_groups_members gm ON u.id = gm.userid
WHERE gm.groupid = ' . $groupid . '
AND c.instanceid = ' . $course->id . '
AND r.shortname = "student"
ORDER BY u.lastname ASC';

$groupmembers = $DB->get_records_sql($querygroupmembers, null);

//on va chercher les sections
$sections = getCourseSections($course->id);

//on va chercher toutes les activités
$activities = getCourseActivitiesRapport($course->id);

// Préchargement en masse pour éviter N requêtes SQL dans les boucles
// Récupère toutes les completions du cours en une seule requête
$userids = array_keys($groupmembers);
$completionsMap = [];
$timespentMap = [];
if (!empty($userids)) {
    $useridlist = implode(',', array_map('intval', $userids));

    // Completions e-learning uniquement (hors face2face/folder/smartchfolder)
    $allcompletions = $DB->get_records_sql('
        SELECT cmc.id, cmc.userid, cmc.coursemoduleid, cmc.completionstate
        FROM mdl_course_modules_completion cmc
        JOIN mdl_course_modules cm ON cm.id = cmc.coursemoduleid
        JOIN mdl_modules m ON m.id = cm.module
        WHERE cm.course = ' . intval($course->id) . '
        AND m.name NOT IN (\'face2face\', \'folder\', \'smartchfolder\')
        AND cmc.userid IN (' . $useridlist . ')
    ', null);
    foreach ($allcompletions as $c) {
        $completionsMap[$c->userid][$c->coursemoduleid] = $c->completionstate;
    }

    // Tout le temps passé en une requête
    $alltimespent = $DB->get_records_sql('
        SELECT userid, SUM(timespent) as total
        FROM mdl_smartch_activity_log
        WHERE course = ' . intval($course->id) . '
        AND userid IN (' . $useridlist . ')
        GROUP BY userid
    ', null);
    foreach ($alltimespent as $t) {
        $timespentMap[$t->userid] = $t->total;
    }
}

// Nombre total de modules e-learning avec completion tracking activé (hors face2face/folder/smartchfolder)
$totalElearningWithCompletion = (int) $DB->count_records_sql('
    SELECT COUNT(cm.id)
    FROM mdl_course_modules cm
    JOIN mdl_modules m ON m.id = cm.module
    WHERE cm.course = ' . intval($course->id) . '
    AND cm.completion > 0
    AND m.name NOT IN (\'face2face\', \'folder\', \'smartchfolder\')
', null);

// Nombre de séances présentielles (plannings de la session)
$totalPlanningsSession = 0;
$completedPlanningsSession = 0;
if ($session) {
    $sessionPlannings = $DB->get_records_sql(
        'SELECT id, startdate FROM mdl_smartch_planning WHERE sessionid = ?',
        [$session->id]
    );
    $totalPlanningsSession = count($sessionPlannings);
    foreach ($sessionPlannings as $sp) {
        if ($sp->startdate < time()) $completedPlanningsSession++;
    }
}
$totalModulesWithCompletion = $totalElearningWithCompletion + $totalPlanningsSession;

// Préchargement de tous les plannings de la session en une seule requête
$planningsMap = [];
if ($session) {
    $allplannings = $DB->get_records_sql('
        SELECT DISTINCT sp.id, sp.sectionid, sp.startdate, sp.enddate, sp.geforplanningid
        FROM mdl_smartch_planning sp
        WHERE sp.sessionid = ' . intval($session->id) . '
        ORDER BY sp.startdate ASC
    ', null);
    foreach ($allplannings as $p) {
        $planningsMap[$p->sectionid][] = $p;
    }
}

// Préchargement des activités face2face par section (1 requête)
$activityPlanningsMap = [];
if ($session) {
    $allActivityPlannings = $DB->get_records_sql("
        SELECT cm.id as id, cm.section as sectionid
        FROM mdl_course_modules cm
        JOIN mdl_modules m ON m.id = cm.module
        WHERE cm.course = " . intval($course->id) . "
        AND m.name = 'face2face'
    ", null);
    foreach ($allActivityPlannings as $ap) {
        $activityPlanningsMap[$ap->sectionid][] = $ap;
    }
}

// Précalcul du statut planning par section (évite getPlanningCompletion dans les boucles)
$planningCompletionMap = [];
foreach ($planningsMap as $sectionid => $plannings) {
    $countactivityplanning = isset($activityPlanningsMap[$sectionid]) ? count($activityPlanningsMap[$sectionid]) : 0;
    $planningCompletionMap[$sectionid] = [];
    $countplanning = 1;
    foreach ($plannings as $planning) {
        if ($countplanning <= $countactivityplanning) {
            $planningCompletionMap[$sectionid][] = ($planning->startdate > time()) ? 'Planifiée' : 'Passée';
            $countplanning++;
        }
    }
}

// Activités à exclure du rapport
$excludedActivityNames = ['Support de formation', 'Dossier de ligue', 'Devoir'];

// Filtrer les sections sans activité avant le chunking (évite les pages vides)
$sections = array_filter($sections, function($section) use ($activities, $planningsMap, $excludedActivityNames) {
    if (empty($section->sequence)) return false;
    $tableau = explode(',', $section->sequence);
    foreach ($tableau as $moduleid) {
        foreach ($activities as $activity) {
            if ($activity->id == $moduleid
                && $activity->activityname
                && $activity->activitytype != "folder"
                && !in_array($activity->activityname, $excludedActivityNames)) {
                return true;
            }
        }
    }
    return isset($planningsMap[$section->id]) && count($planningsMap[$section->id]) > 0;
});
$sections = array_values($sections);

// On va découper les sections en groupes de 5
$sectionsChunks = array_chunk($sections, 5);

// Calculer le temps total passé avant la boucle des tableaux
$totaltimespent = 0;
// foreach ($groupmembers as $groupmember) {
//     $totaltimespent += strtotime("1970-01-01 " . getTimeSpentOnCourse($groupmember->id, $course->id) . " UTC");
// }
// // Convertir le temps total en format lisible
// $totaltimespent = format_time($totaltimespent);

foreach ($sectionsChunks as $chunkIndex => $sectionsChunk) {
    if ($chunkIndex > 0) {
        $content .= '<div style="page-break-before: always;"></div>'; // Saut de page
    }
    
    $content .= '<table>';
    $content .= '<tbody>';
    $content .= '<tr>';
    $content .= '<td rowspan="2">Nom Prénom de l\'apprenant</td>';
    // $content .= '<td rowspan="2">Adresse courriel</td>';
    $content .= '<td rowspan="2">N° INNO</td>';
    $content .= '<td rowspan="2">% de progression totale</td>';
    $content .= '<td rowspan="2">Temps total passé</td>';

    // Première ligne avec les noms des sections
    foreach ($sectionsChunk as $section) {

        $totalsectionsplannings = 0;

        if ($session) {
            $sectionsplannings = isset($planningsMap[$section->id]) ? $planningsMap[$section->id] : [];
            $totalsectionsplannings = count($sectionsplannings);
        }

        $tableau = explode(',', $section->sequence);
        $nbmodule = 0;
        foreach ($tableau as $moduleid) {
            $activity = null;
            foreach ($activities as $activityy) {
                if ($activityy->id == $moduleid) {
                    $activity = $activityy;
                    break;
                }
            }
            if ($activity && $activity->activitytype == 'face2face') {
                if ($totalsectionsplannings > 0) {
                    $totalsectionsplannings--;
                    $nbmodule++;
                }
            } else if ($activity && $activity->activityname && $activity->activitytype != "folder" && !in_array($activity->activityname, $excludedActivityNames)) {
                $nbmodule++;
            }
        }
        if ($nbmodule > 0) {
            $sectionname = $section->name ?: "Généralités";
            $content .= '<td colspan="' . $nbmodule . '">' . $sectionname . '</td>';
        }
    }

    $content .= '</tr>';
    $content .= '<tr>';

    // Deuxième ligne avec les noms des activités
    foreach ($sectionsChunk as $section) {
        if ($session) {
            $sectionsplannings = isset($planningsMap[$section->id]) ? $planningsMap[$section->id] : [];
            $totalsectionsplannings = count($sectionsplannings);
        }

        $tableau = explode(',', $section->sequence);
        foreach ($tableau as $moduleid) {
            $activity = null;
            foreach ($activities as $activityy) {
                if ($activityy->id == $moduleid) {
                    $activity = $activityy;
                    break;
                }
            }
            if ($activity && $activity->activitytype == 'face2face') {
                if ($totalsectionsplannings > 0) {
                    $totalsectionsplannings--;
                    $content .= '<td>' . $activity->activityname . '</td>';
                }
            } else if ($activity && $activity->activityname && $activity->activitytype != "folder" && !in_array($activity->activityname, $excludedActivityNames)) {
                $content .= '<td>' . $activity->activityname . '</td>';
            }
        }
    }

    $content .= '</tr>';

    // Lignes des étudiants
    foreach ($groupmembers as $groupmember) {
        // Calcul progression : e-learning + séances passées (même logique que adminteam)
        if ($totalModulesWithCompletion > 0) {
            $userCompletions = isset($completionsMap[$groupmember->id]) ? $completionsMap[$groupmember->id] : [];
            $completedCount = count(array_filter($userCompletions, function($state) { return $state >= 1; })) + $completedPlanningsSession;
            $progressionVal = number_format($completedCount / $totalModulesWithCompletion * 100, 2);
        } else {
            $progressionVal = 0;
        }
        $progression = $progressionVal . '%';
        // Utilise le cache préchargé au lieu d'une requête SQL par apprenant
        $timespent = isset($timespentMap[$groupmember->id]) ? $timespentMap[$groupmember->id] : 0;
        $totaltimespent += $timespent;

        $content .= '<tr>';
        $content .= '<td>' . $groupmember->firstname . ' ' . $groupmember->lastname . '</td>';
        // $content .= '<td>' . $groupmember->email . '</td>';
        $content .= '<td>' . $groupmember->username . '</td>';
        $content .= '<td>' . $progression . '</td>';
        $content .= '<td>' . convert_to_string_minutes($timespent) . '</td>';

        foreach ($sectionsChunk as $section) {
            if ($session) {
                $sectionsplannings = isset($planningsMap[$section->id]) ? $planningsMap[$section->id] : [];
                $totalsectionsplannings = count($sectionsplannings);
            }

            $tableau = explode(',', $section->sequence);
            foreach ($tableau as $moduleid) {
                $activity = null;
                foreach ($activities as $activityy) {
                    if ($activityy->id == $moduleid) {
                        $activity = $activityy;
                        break;
                    }
                }
                if ($activity && $activity->activitytype == 'face2face') {
                    if ($totalsectionsplannings > 0) {
                        // Utilise le cache précalculé au lieu de getPlanningCompletion (évite 2 requêtes SQL par appel)
                        $planningIdx = count(isset($planningsMap[$section->id]) ? $planningsMap[$section->id] : []) - $totalsectionsplannings;
                        $completion = isset($planningCompletionMap[$section->id][$planningIdx]) ? $planningCompletionMap[$section->id][$planningIdx] : '';
                        $content .= '<td>' . $completion . '</td>';
                        $totalsectionsplannings--;
                    }
                } else if ($activity && $activity->activityname && $activity->activitytype != "folder" && !in_array($activity->activityname, $excludedActivityNames)) {
                    // Utilise le cache préchargé au lieu d'une requête SQL par activité/apprenant
                    $completionstate = isset($completionsMap[$groupmember->id][$moduleid]) ? $completionsMap[$groupmember->id][$moduleid] : 0;
                    $completion = ($completionstate >= 1) ? 'X' : '-';
                    $content .= '<td>' . $completion . '</td>';
                }
            }
        }
        $content .= '</tr>';
    }

    // Ligne de progression générale
    if ($chunkIndex == count($sectionsChunks) - 1) { // Seulement sur le dernier tableau
        // Calcul progression équipe depuis les maps préchargés (évite N×M requêtes SQL de getTeamProgress)
        if ($totalModulesWithCompletion > 0 && count($groupmembers) > 0) {
            $allprog = 0;
            foreach ($groupmembers as $gm) {
                $userCompletions = isset($completionsMap[$gm->id]) ? $completionsMap[$gm->id] : [];
                $completedCount = count(array_filter($userCompletions, function($state) { return $state >= 1; }));
                $allprog += $completedCount / $totalModulesWithCompletion * 100;
            }
            $teamProgressStr = floor($allprog / count($groupmembers)) . '%';
        } else {
            $teamProgressStr = 'N/A';
        }
        $content .= '<tr>';
        $content .= '<td>PROGRESSION GÉNÉRALE</td>';
        $content .= '<td></td>';
        $content .= '<td>' . $teamProgressStr . '</td>';
        $content .= '<td>' . convert_to_string_time($totaltimespent) . '</td>';
        $content .= '</tr>';
    }

    $content .= '</tbody>';
    $content .= '</table>';
}

$content .= '<div>';
$content .= '
<p>Terminé : X</p>
<p>Pas terminé : -</p>';

$content .= '</div>';

// echo $content;
// exit;


require_once '../dompdf/autoload.inc.php';

$options = new Options();
// $options->set('defaultFont', 'Zapf-Dingbats');
$options->set('isRemoteEnabled', true);
// $options->set(['defaultFont' => 'Courier', 'isHtml5ParserEnabled' => true, 'isRemoteEnabled' => true]);
$dompdf = new Dompdf($options);
$css = '<link type="text/css" href="' . $CFG->dirroot . '/local/concorde_plugin/exports/export.css" rel="stylesheet" />';

$html = '
<html>
<head>
    <meta http-equiv="Content-Type" content="charset=utf-8" />
    <style type="text/css">
    @page {
        margin: 15px;
      }
      
      
      table {
        border-radius: 5px;
        width: 100%;
      }
      
      thead {
        background: white;
        color: #004686;
        display: table-header-group;
        vertical-align: middle;
      }
      
      tr:nth-child(even) {
        background-color: #f2f2f2;
      }
      
     
      
      th,
      td {
        text-align: center;
      }
      th,
      tr,
      td {
        
      }
      h1,
      h2 {
        color: #004686 !important;
      }
      h2 {
        margin-top: 50px;
        text-decoration: underline !important;
      }
      @font-face {
        font-family: "Open Sans";
        font-style: normal;
        font-weight: normal;
        src: url(http://themes.googleusercontent.com/static/fonts/opensans/v8/cJZKeOuBrn4kERxqtaUH3aCWcynf_cDxXwCLxiixG1c.ttf)
          format("truetype");
      }
      body {
        font-family: "source_sans_proregular", Calibri, Candara, Segoe, Segoe UI,
          Optima, Arial, sans-serif;
        font-size: 0.25rem;
      }
      
    </style>
</head>
<body>
' . $content . '
</body>
</html>
';
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'landscape');
$dompdf->render();
$dompdf->stream('Rapport-' . $course->fullname . '-' . date("d-m-Y") . '.pdf', array("Attachment" => false));
// $dompdf->stream("", array("Attachment" => false));
exit(0);
