<?php

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');

$id = required_param('id', PARAM_INT); // Course ID.
$delete = optional_param('delete', '', PARAM_ALPHANUM); // Confirmation hash.

$course = $DB->get_record('course', array('id' => $id), '*', MUST_EXIST);
$coursecontext = context_course::instance($course->id);

require_login();

if ($SITE->id == $course->id || !can_delete_course($id)) {
    // Can not delete frontpage or don't have permission to delete the course.
    throw new \moodle_exception('cannotdeletecourse');
}

$categorycontext = context_coursecat::instance($course->category);
$PAGE->set_url('/theme/remui/views/deletecourse.php', array('id' => $id));
$PAGE->set_context($categorycontext);
$PAGE->set_pagelayout('admin');
navigation_node::override_active_url(new moodle_url('/course/management.php', array('categoryid'=>$course->category)));

$courseshortname = format_string($course->shortname, true, array('context' => $coursecontext));
$coursefullname = format_string($course->fullname, true, array('context' => $coursecontext));
//modification smartch course delete
// $categoryurl = new moodle_url('/course/management.php', array('categoryid' => $course->category));
$categoryurl = new moodle_url('/theme/remui/views/adminformations.php');

// Check if we've got confirmation.
if ($delete === md5($course->timemodified)) {
    // We do - time to delete the course.
    require_sesskey();

    $strdeletingcourse = get_string("deletingcourse", "", $courseshortname);

    $PAGE->navbar->add($strdeletingcourse);
    $PAGE->set_title($strdeletingcourse);
    $PAGE->set_heading($SITE->fullname);

    echo $OUTPUT->header();
    echo $OUTPUT->heading($strdeletingcourse);
    // This might take a while. Raise the execution time limit.
    core_php_time_limit::raise();
    // We do this here because it spits out feedback as it goes.
    delete_course($course);
    echo $OUTPUT->heading( get_string("deletedcourse", "", $courseshortname) );
    // Update course count in categories.
    fix_course_sortorder();
    echo $OUTPUT->continue_button($categoryurl);
    echo $OUTPUT->footer();
    exit; // We must exit here!!!
}

$strdeletecheck = get_string("deletecheck", "", $courseshortname);

$PAGE->navbar->add($strdeletecheck);
$PAGE->set_title($strdeletecheck);
$PAGE->set_heading($SITE->fullname);
echo $OUTPUT->header();

// Only let user delete this course if there is not an async backup in progress.
if (!async_helper::is_async_pending($id, 'course', 'backup')) {
    $strdeletecoursecheck = get_string("deletecoursecheck");
    $message = "{$strdeletecoursecheck}<br /><br />{$coursefullname} ({$courseshortname})";

    $continueurl = new moodle_url('/theme/remui/views/deletecourse.php', array('id' => $course->id, 'delete' => md5($course->timemodified)));
    $continuebutton = new single_button(
        $continueurl,
        get_string('delete'), 'post', false, ['data-action' => 'delete']
    );
    echo $OUTPUT->confirm($message, $continuebutton, $categoryurl);
    // In the following script, we need to use setTimeout as disabling the
    // button in the event listener script prevent the click to be taken into account.
    $jsscript = <<<EOF
const button = document.querySelector('button[data-action="delete"]');
if (button) {
    button.addEventListener('click', () => {
        setTimeout(() => {
            button.disabled = true;
        }, 0);
    });
}
EOF;
    $PAGE->requires->js_amd_inline($jsscript);

} else {
    // Async backup is pending, don't let user delete course.
    echo $OUTPUT->notification(get_string('pendingasyncerror', 'backup'), 'error');
    echo $OUTPUT->container(get_string('pendingasyncdeletedetail', 'backup'));
    echo $OUTPUT->continue_button($categoryurl);
}

echo $OUTPUT->footer();
exit;
