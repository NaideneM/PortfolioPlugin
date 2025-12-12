<?php
namespace assignsubmission_portfolio\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Event triggered when a portfolio is submitted.
 */
class portfolio_submitted extends \core\event\base {

    protected function init(): void {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'assign_submission';
    }

    public static function get_name(): string {
        return get_string('event_portfolio_submitted', 'assignsubmission_portfolio');
    }

    public function get_description(): string {
        return "The user with id '{$this->userid}' submitted a portfolio.";
    }

    public function get_url(): \moodle_url {
        return new \moodle_url('/mod/assign/view.php', [
            'id' => $this->contextinstanceid
        ]);
    }
}
