<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Renderer for the Portfolio submission plugin.
 */
class assignsubmission_portfolio_renderer extends plugin_renderer_base {

    /**
     * Render the submission page for a student.
     */
    public function render_submission_page(
        int $userid,
        int $assignid,
        int $cmid
    ): string {

        $data = assignsubmission_portfolio_helper::prepare_submission_page_data(
            $userid,
            $assignid,
            $cmid
        );

        $renderable = new \assignsubmission_portfolio\output\submission_page($data);

        return $this->render($renderable);
    }
}
