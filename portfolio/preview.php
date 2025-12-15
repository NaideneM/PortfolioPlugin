<?php
// Portfolio Submission – Preview PDF endpoint.

require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/locallib.php');

require_login();

$assignid = required_param('assignid', PARAM_INT);
$cmid     = required_param('cmid', PARAM_INT);
$userid   = optional_param('userid', $USER->id, PARAM_INT);

// Resolve course module and context.
$cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);

// Capability checks.
require_capability('mod/assign:view', $context);

// Fetch assignment.
$assign = $DB->get_record('assign', ['id' => $assignid], '*', MUST_EXIST);

// Generate preview PDF.
$pdfcontent = \assignsubmission_portfolio\preview_controller::generate_preview(
    $assign,
    $userid,
    $cmid
);

// Output PDF.
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="portfolio_preview.pdf"');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Length: ' . strlen($pdfcontent));

echo $pdfcontent;
exit;
