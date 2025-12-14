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
     */
    public static function get_latest_module_docx(
        int $userid,
        int $cmid
    ): ?stored_file {

        $context = context_module::instance($cmid);
        $fs = get_file_storage();

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
     * Module completion = DOCX exists.
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
     * Check if all modules are complete.
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
     * Preview PDF URL.
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
     * Prepare data for submission page.
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
 * Platform / environment helpers
 * ========================================================================= */

/**
 * Detect Windows OS.
 */
function assignsubmission_portfolio_is_windows(): bool {
    return strtoupper(substr(PHP_OS, 0, 3)) === 'WIN';
}

/**
 * Check if LibreOffice is available (Linux only).
 */
function assignsubmission_portfolio_has_libreoffice(): bool {
    if (assignsubmission_portfolio_is_windows()) {
        return false;
    }
    @exec('soffice --version', $out, $code);
    return $code === 0;
}

/* =========================================================================
 * DOCX assembly
 * ========================================================================= */

/**
 * Assemble portfolio DOCX from module documents.
 */
function assignsubmission_portfolio_assemble_docx(
    int $userid,
    int $assignid
): string {

    global $DB;

    $tempdir = make_temp_directory('portfolio_' . $userid . '_' . $assignid);
    $workdir = $tempdir . '/work';
    check_dir_exists($workdir, true, true);

    $assign = $DB->get_record('assign', ['id' => $assignid], '*', MUST_EXIST);
    $user   = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

    $modules  = assignsubmission_portfolio_helper::get_module_ids($assignid);
    $sections = [];

    foreach ($modules as $number => $cmid) {
        $docx = assignsubmission_portfolio_helper::get_latest_module_docx(
            $userid,
            $cmid
        );

        if ($docx) {
            $path = $workdir . "/module_{$number}.docx";
            $docx->copy_content_to($path);
            $sections[] = [
                'number' => $number,
                'path'   => $path,
            ];
        }
    }

    if (empty($sections)) {
        throw new moodle_exception('No module documents found to assemble.');
    }

    // Windows fallback: create a placeholder DOCX.
    if (assignsubmission_portfolio_is_windows()) {
        $outfile = $tempdir . '/portfolio_stub.docx';
        $content = "PORTFOLIO (Windows fallback)\n\n"
                 . "Student: {$user->firstname} {$user->lastname}\n"
                 . "Assignment: {$assign->name}\n\n";

        foreach ($sections as $section) {
            $content .= "Module {$section['number']} included.\n";
        }

        file_put_contents($outfile, $content);
        return $outfile;
    }

    // Linux: real LibreOffice master document.
    $masterodt = $workdir . '/portfolio_master.odt';
    assignsubmission_portfolio_create_master_odt(
        $masterodt,
        $sections,
        $user,
        $assign
    );

    exec(
        'soffice --headless --convert-to docx --outdir ' .
        escapeshellarg($tempdir) . ' ' .
        escapeshellarg($masterodt)
    );

    $finaldocx = $tempdir . '/portfolio_master.docx';

    if (!file_exists($finaldocx)) {
        throw new moodle_exception('Failed to generate final DOCX.');
    }

    return $finaldocx;
}

/**
 * Convert DOCX to PDF using LibreOffice.
 */
function assignsubmission_portfolio_convert_docx_to_pdf(string $docxpath): string {

    if (assignsubmission_portfolio_is_windows()) {
        return '';
    }

    $outdir = dirname($docxpath);

    exec(
        'soffice --headless --convert-to pdf --outdir ' .
        escapeshellarg($outdir) . ' ' .
        escapeshellarg($docxpath)
    );

    $pdfpath = preg_replace('/\.docx$/', '.pdf', $docxpath);

    if (!file_exists($pdfpath)) {
        throw new moodle_exception('Failed to convert DOCX to PDF.');
    }

    return $pdfpath;
}

/* =========================================================================
 * Preview / submission output
 * ========================================================================= */

/**
 * Generate preview PDF.
 */
function assignsubmission_portfolio_generate_preview_pdf(
    stdClass $assign,
    int $userid
): string {

    $docx = assignsubmission_portfolio_assemble_docx(
        $userid,
        $assign->id
    );

    if (assignsubmission_portfolio_is_windows()) {
        return assignsubmission_portfolio_render_pdf(
            "Preview mode (Windows)\n\nDOCX assembled successfully:\n{$docx}\n\n" .
            "Full PDF generation runs on Linux servers."
        );
    }

    $pdf = assignsubmission_portfolio_convert_docx_to_pdf($docx);
    return file_get_contents($pdf);
}

/**
 * Generate final submission PDF.
 */
function assignsubmission_portfolio_generate_final_pdf(
    int $userid,
    int $assignid
): string {

    $docx = assignsubmission_portfolio_assemble_docx(
        $userid,
        $assignid
    );

    if (assignsubmission_portfolio_is_windows()) {
        return assignsubmission_portfolio_render_pdf(
            "Submission received (Windows fallback).\n\nDOCX:\n{$docx}\n\n" .
            "Final PDF will be generated on Linux server."
        );
    }

    $pdf = assignsubmission_portfolio_convert_docx_to_pdf($docx);
    return file_get_contents($pdf);
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
