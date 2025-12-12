<?php

defined('MOODLE_INTERNAL') || die();

use assignsubmission_portfolio\event\portfolio_submitted;
use assignsubmission_portfolio\attempts_manager;
use assignsubmission_portfolio\generator;

/**
 * Plugin name.
 *
 * @return string
 */
function assignsubmission_portfolio_pluginname(): string {
    return get_string('pluginname', 'assignsubmission_portfolio');
}

/**
 * Render the student submission page.
 *
 * @param stdClass $submission
 * @param stdClass $assign
 * @param context $context
 * @return string
 */
function assignsubmission_portfolio_view(
    $submission,
    $assign,
    $context
): string {

    global $PAGE;

    /** @var assignsubmission_portfolio_renderer $renderer */
    $renderer = $PAGE->get_renderer('assignsubmission_portfolio');

    return $renderer->render_submission_page(
        $submission->userid,
        $assign->id,
        $context->instanceid
    );
}

/**
 * Save the portfolio submission.
 *
 * This is called when the student submits or resubmits.
 *
 * @param stdClass $submission
 * @param stdClass $data
 * @return bool
 */
function assignsubmission_portfolio_save(
    stdClass $submission,
    stdClass $data
): bool {

    // Only act when our submit button is used.
    if (empty($data->portfolio_submit)) {
        return true;
    }

    $userid   = $submission->userid;
    $assignid = $submission->assignment;
    $cmid     = $submission->cmid;

    // Generate final portfolio PDF.
    $pdfcontent = generator::generate_final_pdf(
        $userid,
        $assignid
    );

    // Store PDF for this attempt.
    attempts_manager::store_submission_file(
        $userid,
        $assignid,
        $submission->id,
        $pdfcontent,
        $cmid
    );

    // Trigger portfolio submitted event.
    $context = context_module::instance($cmid);

    $event = portfolio_submitted::create([
        'objectid' => $submission->id,
        'context'  => $context,
        'userid'   => $userid,
    ]);

    $event->trigger();

    return true;
}
