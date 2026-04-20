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
    public function start_attempt_button($buttontext, \moodle_url $url, $backtoattempturl = null) {
        global $DB, $USER, $PAGE;

        $cm = $PAGE->cm;
        if (!$cm) {
            return parent::start_attempt_button($buttontext, $url, $backtoattempturl);
        }

        $courseid = $cm->course;
        $quizid   = $cm->instance;

        // Latest enrollment date for this user in this course
        $enrolrow = $DB->get_record_sql(
            'SELECT MAX(ue.timecreated) as latest
             FROM mdl_user_enrolments ue
             JOIN mdl_enrol e ON e.id = ue.enrolid
             WHERE ue.userid = ? AND e.courseid = ?',
            [$USER->id, $courseid]
        );
        $since = ($enrolrow && $enrolrow->latest) ? (int)$enrolrow->latest : 0;

        // Count non-abandoned attempts started after latest enrollment
        $attempts = (int)$DB->count_records_sql(
            "SELECT COUNT(*) FROM mdl_quiz_attempts
             WHERE userid = ? AND quiz = ? AND state <> 'abandoned' AND timestart >= ?",
            [$USER->id, $quizid, $since]
        );

        if ($attempts >= 1) {
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

        return parent::start_attempt_button($buttontext, $url, $backtoattempturl);
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
