<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Portfolio helper functions.
 */
class assignsubmission_portfolio_helper {

    /**
     * Get configured module activity cmids for this assignment.
     *
     * Fixed at 5 modules for now.
     *
     * @param int $assignid
     * @return array module_number => cmid
     */
    public static function get_module_ids(int $assignid): array {
        global $DB;

        $assign = $DB->get_record('assign', ['id' => $assignid], '*', MUST_EXIST);

        $modules = [];

        for ($i = 1; $i <= 5; $i++) {
            $field = "assignsubmission_portfolio_module{$i}";
            if (!empty($assign->$field)) {
                $modules[$i] = (int)$assign->$field;
            }
        }

        return $modules;
    }

    /**
     * Retrieve the latest DOCX file for a module (core Moodle H5P).
     *
     * @param int $userid
     * @param int $cmid
     * @return stored_file|null
     */
    public static function get_latest_module_docx(
        int $userid,
        int $cmid
    ): ?stored_file {

        $context = context_module::instance($cmid);
        $fs = get_file_storage();

        // Common H5P file areas where exports appear.
        $fileareas = ['export', 'content', 'package'];

        $latestfile = null;
        $latesttime = 0;

        foreach ($fileareas as $filearea) {
            $files = $fs->get_area_files(
                $context->id,
                'mod_h5pactivity',
                $filearea,
                false,
                'timemodified DESC',
                false
            );

            foreach ($files as $file) {
                if ((int)$file->get_userid() !== $userid) {
                    continue;
                }

                if (strtolower(pathinfo($file->get_filename(), PATHINFO_EXTENSION)) !== 'docx') {
                    continue;
                }

                if ($file->get_timemodified() > $latesttime) {
                    $latesttime = $file->get_timemodified();
                    $latestfile = $file;
                }
            }
        }

        return $latestfile;
    }

    /**
     * Check whether the student has completed each configured module.
     *
     * Completion = DOCX exists.
     *
     * @param int $userid
     * @param array $modules module_number => cmid
     * @return array module_number => bool
     */
    public static function get_module_completion_status(
        int $userid,
        array $modules
    ): array {

        $status = [];

        foreach ($modules as $modulenumber => $cmid) {
            $status[$modulenumber] =
                self::get_latest_module_docx($userid, $cmid) !== null;
        }

        return $status;
    }

    /**
     * Determine if all modules are completed.
     *
     * @param array $completionstatus
     * @return bool
     */
    public static function all_modules_complete(array $completionstatus): bool {
        foreach ($completionstatus as $status) {
            if (!$status) {
                return false;
            }
        }
        return true;
    }

    /**
     * Returns the URL for the preview PDF shown in the iframe.
     */
    public static function get_preview_pdf_url(
        int $userid,
        int $assignid,
        int $cmid
    ): moodle_url {

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
     * Prepare data for the submission page renderer.
     */
    public static function prepare_submission_page_data(
        int $userid,
        int $assignid,
        int $cmid
    ): array {

        $modules     = self::get_module_ids($assignid);
        $completion  = self::get_module_completion_status($userid, $modules);
        $allcomplete = self::all_modules_complete($completion);

        return [
            'modules' => $modules,
            'completion' => $completion,

            'allcomplete' => $allcomplete,
            'integritycheckenabled' => $allcomplete,
            'submitenabled' => $allcomplete,

            'previewurl' => self::get_preview_pdf_url($userid, $assignid, $cmid),
        ];
    }
}

/* =========================================================================
 * DOCX ASSEMBLY (Phase K3)
 * ========================================================================= */

/**
 * Assemble the portfolio DOCX from module documents.
 *
 * @param int $userid
 * @param int $assignid
 * @return string Path to assembled DOCX file
 */
function assignsubmission_portfolio_assemble_docx(
    int $userid,
    int $assignid
): string {

    global $CFG, $DB;

    // Temporary working directory.
    $tempdir = make_temp_directory('portfolio_' . $userid . '_' . $assignid);
    $outfile = $tempdir . '/portfolio.docx';

    // Load assignment + user.
    $assign = $DB->get_record('assign', ['id' => $assignid], '*', MUST_EXIST);
    $user   = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

    // NOTE:
    // PhpWord is not yet bundled. This is a safe structural stub.
    // In Phase K4 we will either:
    //  - bundle PhpWord, or
    //  - swap to LibreOffice conversion.

    $content = "Portfolio DOCX (stub)\n\n"
             . "Student: {$user->firstname} {$user->lastname}\n"
             . "Assignment: {$assign->name}\n\n";

    $modules = assignsubmission_portfolio_helper::get_module_ids($assignid);

    foreach ($modules as $modulenumber => $cmid) {
        $docx = assignsubmission_portfolio_helper::get_latest_module_docx(
            $userid,
            $cmid
        );

        if ($docx) {
            $content .= "Module {$modulenumber}: DOCX found ({$docx->get_filename()})\n";
        } else {
            $content .= "Module {$modulenumber}: No document\n";
        }
    }

    // Write stub DOCX (plain text for now).
    file_put_contents($outfile, $content);

    return $outfile;
}

/* =========================================================================
 * PREVIEW / SUBMISSION OUTPUT
 * ========================================================================= */

/**
 * Generate preview PDF (temporary stub).
 */
function assignsubmission_portfolio_generate_preview_pdf(
    stdClass $assign,
    int $userid
): string {

    $docxpath = assignsubmission_portfolio_assemble_docx(
        $userid,
        $assign->id
    );

    return assignsubmission_portfolio_render_pdf(
        "Preview generated.\n\nAssembled DOCX:\n{$docxpath}"
    );
}

/**
 * Generate final portfolio PDF (temporary stub).
 */
function assignsubmission_portfolio_generate_final_pdf(
    int $userid,
    int $assignid
): string {

    $docxpath = assignsubmission_portfolio_assemble_docx(
        $userid,
        $assignid
    );

    return assignsubmission_portfolio_render_pdf(
        "Final submission generated.\n\nAssembled DOCX:\n{$docxpath}"
    );
}

/**
 * Minimal PDF renderer.
 */
function assignsubmission_portfolio_render_pdf(string $text): string {

    $text = str_replace(['(', ')'], ['\\(', '\\)'], $text);

    $pdf  = "%PDF-1.4\n";
    $pdf .= "1 0 obj << /Type /Catalog /Pages 2 0 R >> endobj\n";
    $pdf .= "2 0 obj << /Type /Pages /Kids [3 0 R] /Count 1 >> endobj\n";
    $pdf .= "3 0 obj << /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] "
          . "/Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >> endobj\n";
    $pdf .= "4 0 obj << /Length " . strlen($text) . " >> stream\n";
    $pdf .= "BT /F1 12 Tf 72 720 Td ({$text}) Tj ET\n";
    $pdf .= "endstream endobj\n";
    $pdf .= "5 0 obj << /Type /Font /Subtype /Type1 /BaseFont /Helvetica >> endobj\n";
    $pdf .= "xref\n0 6\n0000000000 65535 f \n";
    $pdf .= "trailer << /Size 6 /Root 1 0 R >>\n";
    $pdf .= "startxref\n" . strlen($pdf) . "\n%%EOF";

    return $pdf;
}
