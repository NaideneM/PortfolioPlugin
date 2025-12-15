<?php
// Main submission plugin class.

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/submissionplugin.php');

/**
 * Portfolio submission plugin.
 */
class assign_submission_portfolio extends assign_submission_plugin {

    /**
     * Is this submission plugin enabled?
     */
    public function is_enabled(): bool {
        return assignsubmission_portfolio_is_enabled();
    }

    /**
     * Are submissions allowed?
     */
    public function is_submission_allowed(): bool {
        return true;
    }

    /**
     * Add plugin-specific settings to the assignment settings form (Module 1–5 selectors).
     */
    public function get_settings(MoodleQuickForm $mform): void {
        if (!$this->is_enabled()) {
            // Still show settings if admins want to configure while disabled globally?
            // In Moodle, it’s usually fine to always show. We'll show regardless.
        }
        assignsubmission_portfolio_get_settings($mform);
    }

    /**
     * Add form elements shown to students.
     *
     * Moodle owns the form submit button — we only add fields here.
     */
    public function get_form_elements_for_user(
        MoodleQuickForm $mform,
        stdClass $submission,
        stdClass $data,
        int $userid
    ): void {

        if (!$this->is_enabled()) {
            return;
        }

        // Academic integrity declaration.
        $mform->addElement(
            'advcheckbox',
            'integrity_check',
            get_string('integritylabel', 'assignsubmission_portfolio'),
            get_string('integritycheck', 'assignsubmission_portfolio')
        );

        // Client-side required rule.
        $mform->addRule(
            'integrity_check',
            null,
            'required',
            null,
            'client'
        );
    }

    /**
     * Validate submission (server-side).
     */
    public function validate(
        stdClass $data,
        array $files,
        array &$errors
    ): void {

        // Delegate to shared validation logic (keeps behaviour consistent).
        assignsubmission_portfolio_validate($data, $files, $errors);
    }

    /**
     * Save submission.
     */
    public function save(stdClass $submission, stdClass $data): bool {
        return assignsubmission_portfolio_save($submission, $data);
    }

    /**
     * Render student view (preview, module status, messaging).
     */
    public function view(stdClass $submission): string {
        return assignsubmission_portfolio_view(
            $submission,
            $this->assignment->get_instance(),
            $this->assignment->get_context()
        );
    }

    /**
     * Files for grading / download.
     */
    public function get_files(
        stdClass $submission,
        stdClass $context
    ): array {
        return assignsubmission_portfolio_get_files($submission, $context);
    }

    /**
     * Summary shown to tutors in grading table.
     */
    public function view_summary(
        stdClass $submission,
        stdClass $context,
        string $linktext = ''
    ): string {
        return assignsubmission_portfolio_view_summary(
            $submission,
            $context,
            $linktext
        );
    }

    /**
     * Has the user submitted?
     */
    public function has_user_submitted(stdClass $submission): bool {
        $files = assignsubmission_portfolio_get_files(
            $submission,
            $this->assignment->get_context()
        );
        return !empty($files);
    }

    /**
     * Delete submission instance (no custom cleanup needed).
     */
    public function delete_instance(): bool {
        return true;
    }
}
