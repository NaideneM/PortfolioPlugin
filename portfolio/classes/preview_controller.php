<?php
namespace assignsubmission_portfolio;

defined('MOODLE_INTERNAL') || die();

use context_module;
use required_capability_exception;
use stdClass;

/**
 * Controller responsible for portfolio preview generation.
 */
class preview_controller {

    /**
     * Generate preview PDF content.
     *
     * @param stdClass $assign
     * @param int $userid
     * @param int $cmid
     * @return string PDF binary
     */
    public static function generate_preview(
        stdClass $assign,
        int $userid,
        int $cmid
    ): string {

        $context = context_module::instance($cmid);

        // Capability check.
        if ($userid !== $GLOBALS['USER']->id &&
            !has_capability('mod/assign:grade', $context)) {

            throw new required_capability_exception(
                $context,
                'mod/assign:grade',
                'nopermissions',
                ''
            );
        }

        // Delegate to generator.
        return generator::generate_preview_pdf(
            $userid,
            $assign->id
        );
    }
}
