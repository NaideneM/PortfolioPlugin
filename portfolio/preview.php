<?php
// Portfolio Submission – Preview PDF endpoint.

require_once(__DIR__ . '/../../../../config.php');

require_login();

$assignid = required_param('assignid', PARAM_INT);
$cmid     = required_param('cmid', PARAM_INT);
$userid   = optional_param('userid', $USER->id, PARAM_INT);

$cm = get_coursemodule_from_id('assign', $cmid, 0, false, MUST_EXIST);
$context = context_module::instance($cm->id);

require_capability('mod/assign:view', $context);

$assign = $DB->get_record('assign', ['id' => $assignid], '*', MUST_EXIST);

$pdfcontent = \assignsubmission_portfolio\preview_controller::generate_preview(
    $assign,
    $userid,
    $cmid
);

header('Content-Type: application/pdf');
header('Content-Disposition: inline; filename="portfolio_preview.pdf"');
header('Cache-Control: no-store, no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');
header('Content-Length: ' . strlen($pdfcontent));

echo $pdfcontent;
exit;
