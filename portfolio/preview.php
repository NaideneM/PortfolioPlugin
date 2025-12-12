<?php
// This file generates a temporary preview PDF for the Portfolio Submission iframe.

require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/locallib.php');

require_login();

// Parameters.
$userid = optional_param('userid', $USER->id, PARAM_INT);

// Security: students can only preview their own portfolio.
if ($userid !== $USER->id && !has_capability('moodle/site:config', context_system::instance())) {
    throw new required_capability_exception(
        context_system::instance(),
        'moodle/site:config',
        'nopermissions',
        ''
    );
}

// Generate a temporary preview PDF.
// For now, this is a dummy PDF to confirm iframe + headers work.
$pdfcontent = generate_dummy_preview_pdf($userid);

// Send PDF headers.
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="portfolio_preview.pdf"');
header('Content-Length: ' . strlen($pdfcontent));

// Output PDF.
echo $pdfcontent;
exit;

/**
 * Generates a very simple dummy PDF.
 * This will be replaced later with real portfolio collation.
 *
 * @param int $userid
 * @return string
 */
function generate_dummy_preview_pdf(int $userid): string {
    // Minimal valid PDF structure.
    $text = "Portfolio Preview\n\n"
          . "Student ID: {$userid}\n\n"
          . "This is a temporary preview PDF.\n"
          . "Final portfolio content will appear here once implemented.";

    // Escape parentheses.
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

    $offsets = [0];
    foreach (explode("endobj\n", $pdf) as $chunk) {
        $offsets[] = strlen(implode("endobj\n", array_slice(explode("endobj\n", $pdf), 0, count($offsets))));
    }

    foreach ($offsets as $offset) {
        $pdf .= sprintf("%010d 00000 n \n", $offset);
    }

    $pdf .= "trailer << /Size 6 /Root 1 0 R >>\n";
    $pdf .= "startxref\n" . strlen($pdf) . "\n%%EOF";

    return $pdf;
}
