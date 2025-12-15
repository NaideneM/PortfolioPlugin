<?php
// Main submission plugin class.

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/assign/submissionplugin.php');

/**
 * Portfolio submission plugin.
 */
class assign_submission_portfolio extends assign_submission_plugin {

    /**
     * Is this submission plugin enabled?
     */
    public function is_enabled(): bool {
        return (bool)get_config('assignsubmission_portfolio', 'default');
    }

    /**
     * Are submissions allowed?
     */
    public function is_submission_allowed(): bool {
        return true;
    }

    /**
     * Save submission.
     */
    public function save(stdClass $submission, stdClass $data): bool {
        return assignsubmission_portfolio_save($submission, $data);
    }

    /**
     * Student view.
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
     * Summary shown to tutors.
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
     * Assignment settings.
     */
    public function get_settings(MoodleQuickForm $mform): void {
        assignsubmission_portfolio_get_settings($mform);
    }

    /**
     * Validate submission.
     */
    public function validate(
        stdClass $data,
        array $files,
        array &$errors
    ): void {
        assignsubmission_portfolio_validate($data, $files, $errors);
    }

    /**
     * Has user submitted?
     */
    public function has_user_submitted(stdClass $submission): bool {
        $files = assignsubmission_portfolio_get_files(
            $submission,
            $this->assignment->get_context()
        );
        return !empty($files);
    }

    /**
     * Delete submission data.
     */
    public function delete_instance(): bool {
        return true;
    }
}
