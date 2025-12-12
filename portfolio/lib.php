<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Plugin name.
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
function assignsubmission_portfolio_view($submission, $assign, $context): string {
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
 * @param stdClass $submission
 * @param stdClass $data
 * @return bool
 */
function assignsubmission_portfolio_save(
    stdClass $submission,
    stdClass $data
): bool {

    // Only act on our submission button.
    if (empty($data->portfolio_submit)) {
        return true;
    }

    // Generate final portfolio PDF.
    $pdfcontent = assignsubmission_portfolio_generate_final_pdf(
        $submission->userid,
        $submission->assignment
    );

    // Store PDF using Moodle File API.
    $fs = get_file_storage();
    $context = context_module::instance($submission->cmid);

    // Remove files only for this attempt (older attempts remain).
    $fs->delete_area_files(
        $context->id,
        'assignsubmission_portfolio',
        'submission_files',
        $submission->id
    );

    $fileinfo = [
        'contextid' => $context->id,
        'component' => 'assignsubmission_portfolio',
        'filearea'  => 'submission_files',
        'itemid'    => $submission->id,
        'filepath'  => '/',
        'filename'  => 'Portfolio.pdf',
    ];

    $fs->create_file_from_string($fileinfo, $pdfcontent);

    return true;
}
