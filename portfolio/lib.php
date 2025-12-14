<?php

defined('MOODLE_INTERNAL') || die();

use assignsubmission_portfolio\event\portfolio_submitted;
use assignsubmission_portfolio\attempts_manager;

/**
 * Plugin name.
 */
function assignsubmission_portfolio_pluginname(): string {
    return get_string('pluginname', 'assignsubmission_portfolio');
}

/**
 * Whether the plugin is enabled by default.
 */
function assignsubmission_portfolio_is_enabled(): bool {
    return (bool) get_config('assignsubmission_portfolio', 'default');
}

/**
 * Add submission settings to the assignment settings form.
 *
 * @param MoodleQuickForm $mform
 */
function assignsubmission_portfolio_get_settings(MoodleQuickForm $mform): void {
    global $COURSE;

    // Enable portfolio submission.
    $mform->addElement(
        'advcheckbox',
        'assignsubmission_portfolio_enabled',
        get_string('pluginname', 'assignsubmission_portfolio')
    );

    $mform->setDefault(
        'assignsubmission_portfolio_enabled',
        get_config('assignsubmission_portfolio', 'default')
    );

    // Header.
    $mform->addElement(
        'header',
        'assignsubmission_portfolio_modules_header',
        get_string('modulestatusheading', 'assignsubmission_portfolio')
    );

    // Build activity selector list.
    $modinfo = get_fast_modinfo($COURSE);
    $activityoptions = [0 => get_string('none')];

    foreach ($modinfo->cms as $cm) {
        if (!$cm->uservisible) {
            continue;
        }

        $activityoptions[$cm->id] =
            format_string($cm->name) . ' (' . $cm->modname . ')';
    }

    // Fixed Module 1–5 selectors.
    for ($i = 1; $i <= 5; $i++) {
        $mform->addElement(
            'select',
            "assignsubmission_portfolio_module{$i}",
            "Module {$i}",
            $activityoptions
        );
        $mform->setDefault("assignsubmission_portfolio_module{$i}", 0);
    }
}

/**
 * Render the student submission page.
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
 * Validate before submission (server-side).
 */
function assignsubmission_portfolio_validate(
    $data,
    $files,
    &$errors
): void {

    if (empty($data->portfolio_submit)) {
        return;
    }

    if (empty($data->integrity_check)) {
        $errors['integrity_check'] =
            get_string('integritycheck', 'assignsubmission_portfolio');
    }
}

/**
 * Save the portfolio submission.
 */
function assignsubmission_portfolio_save(
    stdClass $submission,
    stdClass $data
): bool {

    if (empty($data->portfolio_submit)) {
        return true;
    }

    $userid   = $submission->userid;
    $assignid = $submission->assignment;
    $cmid     = $submission->cmid;

    // Assemble DOCX.
    $docxpath = assignsubmission_portfolio_assemble_docx(
        $userid,
        $assignid
    );

    // Convert DOCX → PDF.
    $pdfpath = assignsubmission_portfolio_convert_docx_to_pdf($docxpath);
    $pdfcontent = file_get_contents($pdfpath);

    // Store both PDF and DOCX for this attempt.
    attempts_manager::store_submission_files(
        $userid,
        $assignid,
        $submission->id,
        $pdfcontent,
        $docxpath,
        $cmid
    );

    // Trigger submission event.
    $context = context_module::instance($cmid);

    $event = portfolio_submitted::create([
        'objectid' => $submission->id,
        'context'  => $context,
        'userid'   => $userid,
    ]);

    $event->trigger();

    return true;
}

/**
 * Provide submission files to Moodle (grading, preview, download).
 */
function assignsubmission_portfolio_get_files(
    stdClass $submission,
    stdClass $context
): array {

    $fs = get_file_storage();

    return $fs->get_area_files(
        $context->id,
        'assignsubmission_portfolio',
        'submission_files',
        $submission->id,
        'filename',
        false
    );
}

/**
 * Describe file areas for this submission plugin.
 */
function assignsubmission_portfolio_get_file_areas(): array {
    return [
        'submission_files' =>
            get_string('submission', 'assignsubmission_portfolio'),
    ];
}

/**
 * Display a summary of the submission for the grading table.
 *
 * Shows attempt number and submission date.
 */
function assignsubmission_portfolio_view_summary(
    stdClass $submission,
    stdClass $context,
    string $linktext = ''
): string {

    $fs = get_file_storage();

    $files = $fs->get_area_files(
        $context->id,
        'assignsubmission_portfolio',
        'submission_files',
        $submission->id,
        'filename',
        false
    );

    if (empty($files)) {
        return get_string('nosubmission', 'assign');
    }

    $file = reset($files);

    $url = moodle_url::make_pluginfile_url(
        $file->get_contextid(),
        $file->get_component(),
        $file->get_filearea(),
        $file->get_itemid(),
        $file->get_filepath(),
        $file->get_filename(),
        true
    );

    $attempt = isset($submission->attemptnumber)
        ? $submission->attemptnumber + 1
        : 1;

    $submitted = userdate($submission->timemodified);

    return html_writer::div(
        html_writer::link(
            $url,
            get_string('submission', 'assignsubmission_portfolio'),
            ['target' => '_blank']
        ) .
        html_writer::empty_tag('br') .
        html_writer::span("Attempt {$attempt}") .
        html_writer::empty_tag('br') .
        html_writer::span("Submitted: {$submitted}"),
        'assignsubmission-portfolio-summary'
    );
}
