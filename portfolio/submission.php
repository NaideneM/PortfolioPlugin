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
     * Is this submission plugin enabled for this assignment?
     *
     * @return bool
     */
    public function is_enabled(): bool {
        return (bool) get_config('assignsubmission_portfolio', 'default');
    }

    /**
     * Are submissions allowed?
     *
     * @return bool
     */
    public function is_submission_allowed(): bool {
        return true;
    }

    /**
     * Save submission data.
     *
     * @param stdClass $submission
     * @param stdClass $data
     * @return bool
     */
    public function save(
        stdClass $submission,
        stdClass $data
    ): bool {
        return assignsubmission_portfolio_save($submission, $data);
    }

    /**
     * Display the submission to the student.
     *
     * @param stdClass $submission
     * @param bool $showviewlink
     * @return string
     */
    public function view(
        stdClass $submission,
        bool $showviewlink
    ): string {
        return assignsubmission_portfolio_view(
            $submission,
            $this->assignment->get_instance(),
            $this->assignment->get_context()
        );
    }

    /**
     * Return submission files (used by grading, download, etc).
     *
     * @param stdClass $submission
     * @return array
     */
    public function get_files(stdClass $submission): array {
        return assignsubmission_portfolio_get_files(
            $submission,
            $this->assignment->get_context()
        );
    }

    /**
     * Summary shown in the grading table.
     *
     * @param stdClass $submission
     * @param string $linktext
     * @return string
     */
    public function view_summary(
        stdClass $submission,
        string $linktext = ''
    ): string {
        return assignsubmission_portfolio_view_summary(
            $submission,
            $this->assignment->get_context(),
            $linktext
        );
    }

    /**
     * Add settings to the assignment settings form.
     *
     * @param MoodleQuickForm $mform
     */
    public function get_settings(MoodleQuickForm $mform): void {
        assignsubmission_portfolio_get_settings($mform);
    }

    /**
     * Validate submission before save.
     *
     * @param stdClass $data
     * @param array $files
     * @param array $errors
     */
    public function validate(
        stdClass $data,
        array $files,
        array &$errors
    ): void {
        assignsubmission_portfolio_validate($data, $files, $errors);
    }

    /**
     * Has the user submitted anything?
     *
     * @param stdClass $submission
     * @return bool
     */
    public function has_user_submitted(stdClass $submission): bool {
        $files = assignsubmission_portfolio_get_files(
            $submission,
            $this->assignment->get_context()
        );
        return !empty($files);
    }

    /**
     * Delete submission data when assignment instance is deleted.
     *
     * @return bool
     */
    public function delete_instance(): bool {
        return true;
    }
}
