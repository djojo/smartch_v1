<?php

namespace Dompdf;

require_once(__DIR__ . '/../../../config.php');

include $CFG->dirroot . '/theme/remui/views/utils.php';


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


// On va découper les sections en groupes de 10
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
            $sectionsplannings = getSectionPlannings($course->id, $session->id, $section->id);
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
            } else if ($activity && $activity->activityname && $activity->activitytype != "folder") {
                $nbmodule++;
            }
        }
        $sectionname = $section->name ?: "Généralités";
        $content .= '<td colspan="' . $nbmodule . '">' . $sectionname . '</td>';
    }

    $content .= '</tr>';
    $content .= '<tr>';

    // Deuxième ligne avec les noms des activités
    foreach ($sectionsChunk as $section) {
        if ($session) {
            $sectionsplannings = getSectionPlannings($course->id, $session->id, $section->id);
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
            } else if ($activity && $activity->activityname && $activity->activitytype != "folder") {
                $content .= '<td>' . $activity->activityname . '</td>';
            }
        }
    }

    $content .= '</tr>';

    // Lignes des étudiants
    foreach ($groupmembers as $groupmember) {
        $progression = getCompletionPourcent($course->id, $groupmember->id) . '%';
        $timespent = getTotalTimeSpentOnCourse($groupmember->id, $course->id);
        $totaltimespent += $timespent;

        $content .= '<tr>';
        $content .= '<td>' . $groupmember->firstname . ' ' . $groupmember->lastname . ' (' . $groupmember->shortname . ')</td>';
        // $content .= '<td>' . $groupmember->email . '</td>';
        $content .= '<td>' . $groupmember->username . '</td>';
        $content .= '<td>' . $progression . '</td>';
        $content .= '<td>' . convert_to_string_minutes($timespent) . '</td>';

        foreach ($sectionsChunk as $section) {
            if ($session) {
                $sectionsplannings = getSectionPlannings($course->id, $session->id, $section->id);
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
                        $completion = getPlanningCompletion($course->id, $session->id, $section->id);
                        $content .= '<td>' . $completion . '</td>';
                        $totalsectionsplannings--;
                    }
                } else if ($activity && $activity->activityname && $activity->activitytype != "folder") {
                    $completion = getActivityCompletionStatusRapport($moduleid, $groupmember->id);
                    $content .= '<td>' . $completion . '</td>';
                }
            }
        }
        $content .= '</tr>';
    }

    // Ligne de progression générale
    if ($chunkIndex == count($sectionsChunks) - 1) { // Seulement sur le dernier tableau
        $content .= '<tr>';
        $content .= '<td>PROGRESSION GÉNÉRALE</td>';
        $content .= '<td></td>';
        $content .= '<td>' . getTeamProgress($course->id, $groupid)[0] . '</td>';
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
        margin: 50px;
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
        font-size: 0.3rem;
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
