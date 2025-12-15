<?php
namespace assignsubmission_portfolio;

defined('MOODLE_INTERNAL') || die();

use context_module;

/**
 * Handles portfolio submission attempts.
 *
 * Responsible for storing the final generated PDF
 * for each assignment submission attempt.
 */
class attempts_manager {

    /**
     * Store generated portfolio PDF for a submission attempt.
     *
     * @param int $submissionid
     * @param string $pdfcontent
     * @param int $cmid
     * @return void
     */
    public static function store_submission_files(
        int $submissionid,
        string $pdfcontent,
        int $cmid
    ): void {

        $fs = get_file_storage();
        $context = context_module::instance($cmid);

        // Ensure the user has permission to submit.
        require_capability('mod/assign:submit', $context);

        // Remove any existing files for this submission attempt.
        $fs->delete_area_files(
            $context->id,
            'assignsubmission_portfolio',
            'submission_files',
            $submissionid
        );

        // Store the final portfolio PDF.
        $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'assignsubmission_portfolio',
            'filearea'  => 'submission_files',
            'itemid'    => $submissionid,
            'filepath'  => '/',
            'filename'  => 'Portfolio.pdf',
        ], $pdfcontent);
    }
}
