<?php
namespace assignsubmission_portfolio;

defined('MOODLE_INTERNAL') || die();

use context_module;

/**
 * Handles portfolio submission attempts.
 */
class attempts_manager {

    /**
     * Store generated portfolio files for an attempt.
     *
     * @param int $userid
     * @param int $submissionid
     * @param string $pdfcontent
     * @param string|null $docxpath
     * @param int $cmid
     * @return void
     */
    public static function store_submission_files(
        int $userid,
        int $submissionid,
        string $pdfcontent,
        ?string $docxpath,
        int $cmid
    ): void {

        $fs = get_file_storage();
        $context = context_module::instance($cmid);

        require_capability('mod/assign:submit', $context);

        // Remove files for this attempt only.
        $fs->delete_area_files(
            $context->id,
            'assignsubmission_portfolio',
            'submission_files',
            $submissionid
        );

        // Store PDF.
        $fs->create_file_from_string([
            'contextid' => $context->id,
            'component' => 'assignsubmission_portfolio',
            'filearea'  => 'submission_files',
            'itemid'    => $submissionid,
            'filepath'  => '/',
            'filename'  => 'Portfolio.pdf',
        ], $pdfcontent);

        // Store DOCX if available.
        if ($docxpath && file_exists($docxpath)) {
            $fs->create_file_from_pathname([
                'contextid' => $context->id,
                'component' => 'assignsubmission_portfolio',
                'filearea'  => 'submission_files',
                'itemid'    => $submissionid,
                'filepath'  => '/',
                'filename'  => 'Portfolio.docx',
            ], $docxpath);
        }
    }
}
