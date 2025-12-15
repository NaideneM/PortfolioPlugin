<?php
// Main submission plugin class.

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/mod/assign/submissionplugin.php');

/**
 * Portfolio submission plugin.
 */
class assign_submission_portfolio extends assign_submission_plugin {

    public function is_enabled(): bool {
        return assignsubmission_portfolio_is_enabled();
    }

    public function is_submission_allowed(): bool {
        return true;
    }

    public function get_form_elements_for_user(
        MoodleQuickForm $mform,
        stdClass $submission,
        stdClass $data,
        int $userid
    ): void {

        if (!$this->is_enabled()) {
            return;
        }

        $mform->addElement(
            'advcheckbox',
            'integrity_check',
            get_string('integritylabel', 'assignsubmission_portfolio'),
            get_string('integritycheck', 'assignsubmission_portfolio')
        );

        $mform->addRule(
            'integrity_check',
            null,
            'required',
            null,
            'client'
        );
    }

    public function validate(
        stdClass $data,
        array $files,
        array &$errors
    ): void {

        if (empty($data->integrity_check)) {
            $errors['integrity_check'] =
                get_string('integritycheck', 'assignsubmission_portfolio');
        }
    }

    public function save(stdClass $submission, stdClass $data): bool {
        return assignsubmission_portfolio_save($submission, $data);
    }

    public function view(stdClass $submission): string {
        return assignsubmission_portfolio_view(
            $submission,
            $this->assignment->get_instance(),
            $this->assignment->get_context()
        );
    }

    public function get_files(
        stdClass $submission,
        stdClass $context
    ): array {
        return assignsubmission_portfolio_get_files($submission, $context);
    }

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

    public function has_user_submitted(stdClass $submission): bool {
        $files = assignsubmission_portfolio_get_files(
            $submission,
            $this->assignment->get_context()
        );
        return !empty($files);
    }

    public function delete_instance(): bool {
        return true;
    }
}
