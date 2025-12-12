<?php
// Portfolio Submission – Preview PDF endpoint.

require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/locallib.php');

require_login();

// Required parameters.
$assignid = required_param('assignid', PARAM_INT);
$cmid     = required_param('cmid', PARAM_INT);
$userid   = optional_param('userid', $USER->id, PARAM_INT);

// Resolve course module and context.
$cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);

// Capability checks.
require_capability('mod/assign:view', $context);

// Students may only preview their own portfolio.
if ($userid !== $USER->id && !has_capability('mod/assign:grade', $context)) {
    throw new required_capability_exception(
        $context,
        'mod/assign:grade',
        'nopermissions',
        ''
    );
}

// Fetch assignment record.
$assign = $DB->get_record('assign', ['id' => $assignid], '*', MUST_EXIST);

// Generate preview PDF (still dummy for now).
$pdfcontent = assignsubmission_portfolio_generate_preview_pdf($assign, $userid);

// Output PDF headers.
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="portfolio_preview.pdf"');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Length: ' . strlen($pdfcontent));

// Output PDF.
echo $pdfcontent;
exit;

/**
 * Generate a temporary preview PDF.
 *
 * This is intentionally simple and dependency-free.
 * Later this will be replaced with real DOCX collation.
 *
 * @param stdClass $assign
 * @param int $userid
 * @return string
 */
function assignsubmission_portfolio_generate_preview_pdf(stdClass $assign, int $userid): string {
    global $DB;

    $user = $DB->get_record('user', ['id' => $userid], 'id, firstname, lastname', MUST_EXIST);

    $text = "Portfolio Preview\n\n"
          . "Student: {$user->firstname} {$user->lastname}\n"
          . "Assignment: {$assign->name}\n"
          . "Generated: " . userdate(time()) . "\n\n"
          . "This is a preview of the portfolio submission.\n"
          . "Completed module content will appear here.\n\n"
          . "This PDF is not yet submitted.";

    // Escape PDF-sensitive characters.
    $text = str_replace(['(', ')'], ['\\(', '\\)'], $text);

    // Minimal valid PDF structure.
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

    $offsets = [0];
    $objects = explode("endobj\n", $pdf);
    $cursor = 0;

    foreach ($objects as $obj) {
        $offsets[] = $cursor;
        $cursor += strlen($obj . "endobj\n");
    }

    foreach ($offsets as $offset) {
        $pdf .= sprintf("%010d 00000 n \n", $offset);
    }

    $pdf .= "trailer << /Size 6 /Root 1 0 R >>\n";
    $pdf .= "startxref\n" . strlen($pdf) . "\n%%EOF";

    return $pdf;
}
