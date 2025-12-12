<?php
namespace assignsubmission_portfolio;

defined('MOODLE_INTERNAL') || die();

use context_module;

/**
 * Handles portfolio submission attempts.
 */
class attempts_manager {

    /**
     * Store a generated portfolio file for an attempt.
     *
     * @param int $userid
     * @param int $assignid
     * @param int $submissionid
     * @param string $pdfcontent
     * @param int $cmid
     * @return void
     */
    public static function store_submission_file(
        int $userid,
        int $assignid,
        int $submissionid,
        string $pdfcontent,
        int $cmid
    ): void {

        $fs = get_file_storage();
        $context = context_module::instance($cmid);

        // Remove files for this attempt only.
        $fs->delete_area_files(
            $context->id,
            'assignsubmission_portfolio',
            'submission_files',
            $submissionid
        );

        $fileinfo = [
            'contextid' => $context->id,
            'component' => 'assignsubmission_portfolio',
            'filearea'  => 'submission_files',
            'itemid'    => $submissionid,
            'filepath'  => '/',
            'filename'  => 'Portfolio.pdf',
        ];

        $fs->create_file_from_string($fileinfo, $pdfcontent);
    }
}
