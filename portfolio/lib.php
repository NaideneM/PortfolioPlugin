<?php

defined('MOODLE_INTERNAL') || die();

require_once(__DIR__ . '/locallib.php');

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
 */
function assignsubmission_portfolio_get_settings(
    MoodleQuickForm $mform,
    stdClass $assignment,
    context $context
): void {
    global $COURSE;

    $mform->addElement(
        'advcheckbox',
        'assignsubmission_portfolio_enabled',
        get_string('pluginname', 'assignsubmission_portfolio')
    );

    $mform->setDefault(
        'assignsubmission_portfolio_enabled',
        get_config('assignsubmission_portfolio', 'default')
    );

    $mform->addElement(
        'header',
        'assignsubmission_portfolio_modules_header',
        get_string('modulestatusheading', 'assignsubmission_portfolio')
    );

    $modinfo = get_fast_modinfo($COURSE);
    $activityoptions = [0 => get_string('none')];

    foreach ($modinfo->cms as $cm) {
        if ($cm->uservisible) {
            $activityoptions[$cm->id] =
                format_string($cm->name) . ' (' . $cm->modname . ')';
        }
    }

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
    stdClass $submission,
    stdClass $assign,
    context $context
): string {

    global $PAGE;

    $renderer = $PAGE->get_renderer('assignsubmission_portfolio');

    return $renderer->render_submission_page(
        $submission->userid,
        $assign->id,
        $context->instanceid
    );
}

/**
 * Validate before submission.
 */
function assignsubmission_portfolio_validate(
    stdClass $data,
    array $files,
    array &$errors
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

    $context = context_module::instance($cmid);
    require_capability('mod/assign:submit', $context);

    // === PRODUCTION PIPELINE ===
    // DOCX → HTML → PDF

    $assign = $GLOBALS['DB']->get_record(
        'assign',
        ['id' => $assignid],
        '*',
        MUST_EXIST
    );

    // Merge DOCX files to HTML
    $html = assignsubmission_portfolio_merge_docx_to_html(
        $userid,
        $assignid
    );

    // Convert HTML to PDF using DomPDF
    $pdfcontent = assignsubmission_portfolio_render_pdf_from_html($html);

    // Store PDF (DOCX no longer stored)
    attempts_manager::store_submission_files(
        $submission->id,
        $pdfcontent,
        $cmid
    );

    // Trigger portfolio submitted event
    $event = portfolio_submitted::create([
        'objectid' => $submission->id,
        'context'  => $context,
        'userid'   => $userid,
    ]);
    $event->trigger();

    return true;
}

/**
 * Provide submission files to Moodle.
 */
function assignsubmission_portfolio_get_files(
    stdClass $submission,
    context $context
): array {

    return get_file_storage()->get_area_files(
        $context->id,
        'assignsubmission_portfolio',
        'submission_files',
        $submission->id,
        'filename',
        false
    );
}

/**
 * Describe file areas.
 */
function assignsubmission_portfolio_get_file_areas(): array {
    return [
        'submission_files' =>
            get_string('submission', 'assignsubmission_portfolio'),
    ];
}

/**
 * Summary for grading table.
 */
function assignsubmission_portfolio_view_summary(
    stdClass $submission,
    context $context,
    string $linktext = ''
): string {

    $files = assignsubmission_portfolio_get_files($submission, $context);

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

    return html_writer::link(
        $url,
        get_string('submission', 'assignsubmission_portfolio'),
        ['target' => '_blank']
    );
}
