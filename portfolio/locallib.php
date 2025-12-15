<?php

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/vendor/autoload.php');

use PhpOffice\PhpWord\PhpWord;
use PhpOffice\PhpWord\IOFactory;
use Dompdf\Dompdf;
use Dompdf\Options;

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
 * DOCX → HTML → PDF (PRODUCTION PIPELINE)
 * ========================================================================= */

/**
 * Merge module DOCX files into a single HTML document.
 */
function assignsubmission_portfolio_merge_docx_to_html(
    int $userid,
    int $assignid
): string {

    global $DB;

    $phpWord = new PhpWord();
    $section = $phpWord->addSection();

    $assign = $DB->get_record('assign', ['id' => $assignid], '*', MUST_EXIST);
    $user   = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);

    // Cover page
    $section->addTitle('Portfolio', 1);
    $section->addText("Student: {$user->firstname} {$user->lastname}");
    $section->addText("Assignment: {$assign->name}");
    $section->addTextBreak(2);

    $modules = assignsubmission_portfolio_helper::get_module_ids($assignid);

    if (empty($modules)) {
        throw new moodle_exception('No modules configured for this assignment.');
    }

    foreach ($modules as $number => $cmid) {
        $storedfile = assignsubmission_portfolio_helper::get_latest_module_docx(
            $userid,
            $cmid
        );

        if (!$storedfile) {
            continue;
        }

        $tmp = make_temp_directory('portfolio_docx');
        $docxpath = $tmp . "/module_{$number}.docx";
        $storedfile->copy_content_to($docxpath);

        $section->addPageBreak();
        $section->addTitle("Module {$number}", 2);

        $reader = IOFactory::createReader('Word2007');
        $moduleDoc = $reader->load($docxpath);

        foreach ($moduleDoc->getSections() as $modSection) {
            foreach ($modSection->getElements() as $element) {
                $section->addElement(clone $element);
            }
        }
    }

    // Convert merged PhpWord → HTML
    $writer = IOFactory::createWriter($phpWord, 'HTML');
    ob_start();
    $writer->save('php://output');
    $html = ob_get_clean();

    return $html;
}

/**
 * Render HTML to PDF using DomPDF.
 */
function assignsubmission_portfolio_render_pdf_from_html(string $html): string {

    $options = new Options();
    $options->set('isRemoteEnabled', true);
    $options->set('defaultFont', 'Helvetica');

    $dompdf = new Dompdf($options);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    return $dompdf->output();
}

/**
 * Generate preview PDF.
 */
function assignsubmission_portfolio_generate_preview_pdf(
    stdClass $assign,
    int $userid
): string {

    $html = assignsubmission_portfolio_merge_docx_to_html(
        $userid,
        $assign->id
    );

    return assignsubmission_portfolio_render_pdf_from_html($html);
}

/**
 * Generate final submission PDF.
 */
function assignsubmission_portfolio_generate_final_pdf(
    int $userid,
    int $assignid
): string {

    $html = assignsubmission_portfolio_merge_docx_to_html(
        $userid,
        $assignid
    );

    return assignsubmission_portfolio_render_pdf_from_html($html);
}
