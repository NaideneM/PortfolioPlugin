<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Portfolio helper functions for retrieving module attempts,
 * checking completion, and preparing data for the submission page.
 */
class assignsubmission_portfolio_helper {

    /**
     * Returns module IDs configured for this assignment.
     *
     * Later, this may come from assignment settings. For now, stub values.
     *
     * @return array
     */
    public static function get_module_ids() {
        // Placeholder: later replaced with dynamic configuration.
        return [1, 2, 3, 4, 5];
    }

    /**
     * Check whether the student has completed each module.
     *
     * @param int $userid
     * @param array $moduleids
     * @return array keyed by module: true/false
     */
    public static function get_module_completion_status($userid, array $moduleids) {
        $status = [];
        foreach ($moduleids as $module) {
            // Placeholder: later check H5P completion or activity completion.
            $status[$module] = false; 
        }
        return $status;
    }

    /**
     * Retrieve the latest DOCX output for each module.
     *
     * @param int $userid
     * @param array $moduleids
     * @return array module => filepath or null
     */
    public static function get_latest_module_files($userid, array $moduleids) {
        $files = [];
        foreach ($moduleids as $module) {
            // Placeholder for:
            // - Querying H5P attempts
            // - Retrieving attempt file through File API
            $files[$module] = null;  // No files yet.
        }
        return $files;
    }

    /**
     * Determine if all modules are completed.
     *
     * @param array $completionstatus
     * @return bool
     */
    public static function all_modules_complete(array $completionstatus) {
        foreach ($completionstatus as $status) {
            if (!$status) {
                return false;
            }
        }
        return true;
    }

    /**
     * Prepare data for the submission page renderer.
     *
     * @param int $userid
     * @return array
     */
    public static function prepare_submission_page_data(
        int $userid,
        int $assignid,
        int $cmid
    ): array {
        $moduleids = self::get_module_ids();
        $completion = self::get_module_completion_status($userid, $moduleids);
        $allcomplete = self::all_modules_complete($completion);

        return [
            'modules' => $moduleids,
            'completion' => $completion,
            'allcomplete' => $allcomplete,
            'integritycheckenabled' => $allcomplete,
            'submitenabled' => $allcomplete,
            'previewurl' => self::get_preview_pdf_url(
                $userid,
                $data['assignid'],
                $data['cmid']
            ),
        ];
    }

    /**
     * Returns the URL for the preview PDF shown in the iframe.
     *
     * @param int $userid
     * @param int $assignid
     * @param int $cmid
     * @return moodle_url
     */
    public static function get_preview_pdf_url(int $userid, int $assignid, int $cmid): moodle_url {
        return new moodle_url(
            '/mod/assign/submission/portfolio/preview.php',
            [
                'userid'   => $userid,
                'assignid' => $assignid,
                'cmid'     => $cmid,
            ]
        );
    }

    /**
     * Generate the preview PDF (placeholder for now).
     *
     * @param int $userid
     * @return string filepath to temporary preview
     */
    public static function generate_preview_pdf($userid) {
        // Later will merge DOCX modules → preview PDF.
        // For now, just return null.
        return null;
    }

    /**
     * Generate the final PDF on submission (placeholder for now).
     *
     * @param int $userid
     * @param int $assignid
     * @return string filepath of final PDF
     */
    public static function generate_final_portfolio_pdf($userid, $assignid) {
        // Implementation later:
        // - Retrieve DOCX attempts
        // - Merge into final DOCX
        // - Convert to PDF
        // - Return path for file storage
        return null;
    }
}
