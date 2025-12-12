<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Returns the name of the plugin shown in the assignment settings.
 *
 * @return string
 */
function assignsubmission_portfolio_pluginname() {
    return get_string('pluginname', 'assignsubmission_portfolio');
}

/**
 * Add submission elements to the assignment settings form (admin-level).
 *
 * @param MoodleQuickForm $mform
 * @param stdClass $context
 * @return void
 */
function assignsubmission_portfolio_make_submission_form(&$mform, $context) {
    // No form elements required yet.
}

/**
 * Saves the submission data (final PDF) – placeholder for now.
 *
 * @param stdClass $submission
 * @param stdClass $data
 * @return bool
 */
function assignsubmission_portfolio_save(stdClass $submission, stdClass $data) {
    // We will fill this in later when generating the portfolio PDF.
    return true;
}

/**
 * Determines if the plugin is enabled globally.
 *
 * @return bool
 */
function assignsubmission_portfolio_is_enabled() {
    return get_config('assignsubmission_portfolio', 'default');
}

/**
 * Validates before form submission.
 *
 * @param array $data
 * @param array $files
 * @param stdClass $errors
 */
function assignsubmission_portfolio_validate($data, $files, &$errors) {
    // Later we will validate:
    // - All modules completed
    // - Integrity checkbox ticked
    return;
}

/**
 * View submission from the student's perspective.
 *
 * Called when the student opens the Portfolio Submission assignment.
 *
 * @param stdClass $submission
 * @param stdClass $context
 * @return string HTML output
 */
function assignsubmission_portfolio_view($submission, $assign, $context) {
    global $PAGE;

    $userid = $submission->userid;

    /** @var assignsubmission_portfolio_renderer $renderer */
    $renderer = $PAGE->get_renderer('assignsubmission_portfolio');

    return $renderer->render_submission_page($userid);
}

/**
 * Delete submission data when a student's attempt is deleted.
 *
 * @param stdClass $submission
 * @return bool
 */
function assignsubmission_portfolio_delete(stdClass $submission) {
    // We will later delete PDFs from the file storage.
    return true;
}
