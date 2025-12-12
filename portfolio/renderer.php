<?php

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/locallib.php');

/**
 * Renderer for the Portfolio Submission plugin.
 */
class assignsubmission_portfolio_renderer extends plugin_renderer_base {

    /**
     * Render the submission page for the student.
     *
     * @param int $userid
     * @return string HTML
     */
    public function render_submission_page($userid) {
        // Prepare data for the submission UI.
        $data = assignsubmission_portfolio_helper::prepare_submission_page_data(
            $userid,
            $this->page->cm->instance,
            $this->page->cm->id
        );

        // Wrap data in a renderable class (templatable).
        $renderable = new \assignsubmission_portfolio\output\submission_page($data);

        // Render via mustache template.
        return $this->render($renderable);
    }
}
