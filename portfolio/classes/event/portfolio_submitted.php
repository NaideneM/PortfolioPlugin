<?php
namespace assignsubmission_portfolio\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when a portfolio is submitted.
 */
class portfolio_submitted extends \core\event\base {

    /**
     * Initialise event data.
     */
    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'assign_submission';
    }

    /**
     * Validate event data.
     */
    protected function validate_data(): void {
        parent::validate_data();

        if (empty($this->objectid)) {
            throw new \coding_exception('The portfolio_submitted event must define an objectid.');
        }

        if (empty($this->context)) {
            throw new \coding_exception('The portfolio_submitted event must define a context.');
        }
    }

    /**
     * Localised event name.
     */
    public static function get_name(): string {
        return get_string('event_portfolio_submitted', 'assignsubmission_portfolio');
    }

    /**
     * Event description for logs.
     */
    public function get_description(): string {
        return "The user with id '{$this->userid}' submitted a portfolio "
             . "(submission id '{$this->objectid}').";
    }

    /**
     * URL related to the event.
     */
    public function get_url(): \moodle_url {
        return new \moodle_url('/mod/assign/view.php', [
            'id' => $this->contextinstanceid
        ]);
    }

    /**
     * Legacy event data for backward compatibility.
     */
    protected function get_legacy_eventdata(): \stdClass {
        $data = new \stdClass();
        $data->userid = $this->userid;
        $data->submissionid = $this->objectid;
        return $data;
    }
}
