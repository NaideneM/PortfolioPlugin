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

// Generate preview PDF (dummy for now).
$pdfcontent = assignsubmission_portfolio_generate_preview_pdf($assign, $userid);

// Output PDF headers.
header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="portfolio_preview.pdf"');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Length: ' . strlen($pdfcontent));

echo $pdfcontent;
exit;
