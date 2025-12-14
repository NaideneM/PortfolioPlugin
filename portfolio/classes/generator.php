<?php
namespace assignsubmission_portfolio;

defined('MOODLE_INTERNAL') || die();

/**
 * Portfolio document generator.
 */
class generator {

    public static function generate_preview_pdf(
        int $userid,
        int $assignid
    ): string {

        $docxpath = assignsubmission_portfolio_assemble_docx(
            $userid,
            $assignid
        );

        return assignsubmission_portfolio_render_pdf(
            "Preview generated.\n\nAssembled DOCX:\n{$docxpath}"
        );
    }

    public static function generate_final_pdf(
        int $userid,
        int $assignid
    ): string {

        $docxpath = assignsubmission_portfolio_assemble_docx(
            $userid,
            $assignid
        );

        return assignsubmission_portfolio_render_pdf(
            "Final submission generated.\n\nAssembled DOCX:\n{$docxpath}"
        );
    }
}
