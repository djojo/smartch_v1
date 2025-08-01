<?php

defined('MOODLE_INTERNAL') || die();

global $CFG, $COURSE;

user_preference_allow_ajax_update('enable_focus_mode', PARAM_BOOL);

require_once($CFG->dirroot . '/theme/remui/layout/common.php');

if (isset($templatecontext['focusdata']['enabled']) && $templatecontext['focusdata']['enabled']) {
    list(
        $templatecontext['focusdata']['sections'],
        $templatecontext['focusdata']['active']
    ) = \theme_remui\utility::get_focus_mode_sections($COURSE);
}
$coursecontext = context_course::instance($COURSE->id);
if (!is_guest($coursecontext, $USER) && \theme_remui\toolbox::get_setting('enablecoursestats')) {
    $templatecontext['iscoursestatsshow'] = true;
}

$completion = new \completion_info($COURSE);
$templatecontext['completion'] = $completion->is_enabled();

$roles = get_user_roles(context_course::instance($COURSE->id), $USER->id);
$key = array_search('student', array_column($roles, 'shortname'));
if ($key === false || is_siteadmin()) {
    $templatecontext['notstudent'] = true;
}

// Must be called before rendering the template.
// This will ease us to add body classes directly to the array.
require_once($CFG->dirroot . '/theme/remui/layout/common_end.php');

echo $OUTPUT->render_from_template('theme_remui/course', $templatecontext);
