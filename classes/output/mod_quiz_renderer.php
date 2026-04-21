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
 * Edwiser RemUI
 *
 * @package   theme_remui
 * @copyright (c) 2023 WisdmLabs (https://wisdmlabs.com/) <support@wisdmlabs.com>
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace theme_remui\output;

/**
 * The renderer for the quiz module.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_quiz_renderer extends \mod_quiz_renderer {

    /**
     * Override start attempt button: only 1 attempt per enrollment.
     * On re-enrollment, the latest enrollment date resets the counter.
     */
    public function start_attempt_button($buttontext, \moodle_url $url, ?\mod_quiz_preflight_check_form $preflightcheckform = null, $popuprequired = false, $popupoptions = null) {
        global $DB, $USER, $PAGE;

        $cm = $PAGE->cm;
        if (!$cm) {
            return parent::start_attempt_button($buttontext, $url, $preflightcheckform, $popuprequired, $popupoptions);
        }

        $courseid = $cm->course;
        $quizid   = $cm->instance;

        // Find the most recently joined session-group for this user in this course.
        // Each session = one unique group (mdl_groups.id). We use gm.timeadded
        // as the boundary: an attempt is "for" this session if timestart >= timeadded.
        // groupid DESC breaks ties when two groups are added in the same second.
        $latestgroup = $DB->get_record_sql(
            'SELECT gm.groupid, gm.timeadded
             FROM {groups_members} gm
             JOIN {groups} g ON g.id = gm.groupid
             JOIN {smartch_session} ss ON ss.groupid = g.id
             WHERE gm.userid = :userid AND g.courseid = :courseid
             ORDER BY gm.timeadded DESC, gm.groupid DESC
             LIMIT 1',
            ['userid' => $USER->id, 'courseid' => $courseid]
        );

        if (!$latestgroup) {
            return parent::start_attempt_button($buttontext, $url, $preflightcheckform, $popuprequired, $popupoptions);
        }

        // Check if user already has a non-abandoned attempt since joining this session-group.
        $hasattempt = $DB->record_exists_sql(
            "SELECT 1 FROM {quiz_attempts}
             WHERE userid = :userid AND quiz = :quizid
               AND state <> 'abandoned' AND timefinish > 0 AND timestart >= :since",
            ['userid' => $USER->id, 'quizid' => $quizid, 'since' => (int)$latestgroup->timeadded]
        );

        if ($hasattempt) {
            return '<div class="smartch-quiz-attempt-done" style="
                        background: #f0f4ff;
                        border-left: 4px solid #004687;
                        border-radius: 8px;
                        padding: 20px 24px;
                        margin: 16px 0;
                        display: flex;
                        align-items: flex-start;
                        gap: 16px;">
                        <svg style="min-width:24px;color:#004687;" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="24" height="24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.25 11.25l.041-.02a.75.75 0 011.063.852l-.708 2.836a.75.75 0 001.063.853l.041-.021M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9-3.75h.008v.008H12V8.25z" />
                        </svg>
                        <div>
                            <p style="margin:0 0 6px 0;font-family:FFF-Equipe-Bold,sans-serif;color:#004687;font-size:1rem;">
                                Vous avez déjà effectué votre tentative pour cette session.
                            </p>
                            <p style="margin:0;color:#4c5a73;font-size:0.9rem;font-family:FFF-Equipe-Regular,sans-serif;">
                                Une seule tentative est autorisée par inscription. Si vous êtes réinscrit(e) à une nouvelle session, vous pourrez accéder à nouveau à ce test.
                            </p>
                        </div>
                    </div>';
        }

        return parent::start_attempt_button($buttontext, $url, $preflightcheckform, $popuprequired, $popupoptions);
    }

    /**
     * Return the HTML of the quiz timer.
     * @return string HTML content.
     */
    public function countdown_timer(\quiz_attempt $attemptobj, $timenow) {

        $timeleft = $attemptobj->get_time_left_display($timenow);
        $output = '';
        if ($timeleft !== false) {
            $ispreview = $attemptobj->is_preview();
            $timerstartvalue = $timeleft;
            if (!$ispreview) {
                // Make sure the timer starts just above zero. If $timeleft was <= 0, then
                // this will just have the effect of causing the quiz to be submitted immediately.
                $timerstartvalue = max($timerstartvalue, 1);
            }
            $this->initialise_timer($timerstartvalue, $ispreview);
        }

        $output .= $this->output->render_from_template('mod_quiz/timer', (object)[]);

        if (isset($timerstartvalue) && $timerstartvalue != null) {
            $output .= '<div id="quiztimer" class="quiztimer" data-timer="'.($timerstartvalue - 1).'"></div>';
        }

        return $output;
    }
}
