<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * A drawer based layout for the remui theme.
 *
 * @package   theme_remui
 * @copyright (c) 2023 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

user_preference_allow_ajax_update('enable_focus_mode', PARAM_BOOL);

require_once($CFG->dirroot . '/theme/remui/layout/common.php');

if (isset($templatecontext['focusdata']['enabled']) && $templatecontext['focusdata']['enabled']) {
    if (isset($PAGE->cm->id)) {
        list(
            $templatecontext['focusdata']['sections'],
            $templatecontext['focusdata']['active'],
            $templatecontext['focusdata']['previous'],
            $templatecontext['focusdata']['next']
        ) = \theme_remui\utility::get_focus_mode_sections($COURSE, $PAGE->cm->id);
    } else {
        list(
            $templatecontext['focusdata']['sections'],
            $templatecontext['focusdata']['active']
        ) = \theme_remui\utility::get_focus_mode_sections($COURSE);
    }
}

$template = 'theme_remui/incourse';

// Return if not on enrolment page.
if ($PAGE->pagetype == "enrol-index" & get_config('theme_remui', 'enrolment_page_layout')) {
    $extraclasses[] = 'page-enrolment';
    $template = 'theme_remui/enrolpage';

    $eh = new \theme_remui\EnrolmentPageHandler();
    $templatecontext['enrolment'] = $eh->generate_enrolment_page_context($templatecontext);
}

// Must be called before rendering the template.
// This will ease us to add body classes directly to the array.
require_once($CFG->dirroot . '/theme/remui/layout/common_end.php');
require_once($CFG->dirroot . '/theme/remui/views/utils.php');

echo $OUTPUT->render_from_template($template, $templatecontext);

if($PAGE->cm->modname == "quiz"){
    echo '<script>
        // console.log("on cache les boutons de navigation prev-activity-link et next-activity-link");
        document.querySelector("#prev-activity-link").remove();
        document.querySelector("#next-activity-link").remove();
    </script>';
} else if ($PAGE->cm->modname == "face2face") {
    
    $rolename = getMainRole($USER->id);
    
    if ($rolename == "super-admin" || $rolename == "manager" || $rolename == "smalleditingteacher") {
        // echo '<script>
        //     alert("'.$rolename.'");
        // </script>';
    } else{
    
        //On va chercher les activités de la formation dans l'ordre
        $sections = getCourseSections($COURSE->id);

        $isThisSection = false;
        $thisSectionId = null;
        $nextActivity = null;
        foreach($sections as $section){
            if(!$isThisSection){
                // echo $section->sequence;
                $tableact = explode(',', $section->sequence);
                $tableact = array_map('intval', $tableact);
        
                foreach($tableact as $key => $activityNumber){
                    if($activityNumber == $PAGE->cm->id){
                        $isThisSection = true;
                        if($key+1 < count($tableact)){
                            $nextActivity = $tableact[$key+1];
                        } else {
                            $thisSectionId = $section->id;
                        }
                    }
                }
            }
        }
        if($nextActivity){
            echo "<script>console.log('l'activité suivante est : ".$nextActivity."')</script>";
            
            echo '<script>
                let nextBtn = document.querySelector("#next-activity-link");
                console.log(nextBtn);
                if(nextBtn){
                    nextBtn.click();
                } else {
                    window.location.href = "'.$CFG->wwwroot.'/theme/remui/views/formation.php?id='.$COURSE->id. '";
                }
            </script>';

            //On va chercher le module de l'activité suivante
            $nextModule = getModule($nextActivity);
            // var_dump($nextModule);
            //on créer l'url de l'activité suivante
            $nextUrl = $CFG->wwwroot.'/mod/'.$nextModule->activitytype.'/view.php?id='.$nextActivity;
            // var_dump($nextUrl);

        } else {
            echo "<script>console.log('il n'y a pas d'activité suivante');</script>";
            echo $thisSectionId;
            if($thisSectionId){
                redirect($CFG->wwwroot.'/theme/remui/views/formation.php?id='.$COURSE->id.'&sectionid='.$thisSectionId);
            } else {
                redirect($CFG->wwwroot.'/theme/remui/views/formation.php?id='.$COURSE->id);
            }
            
        }
    }
}