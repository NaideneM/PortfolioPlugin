<?php

namespace assignsubmission_portfolio\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;

/**
 * Renderable class for the Portfolio Submission page.
 *
 * IMPORTANT:
 * - Display-only
 * - No form fields
 * - No submission logic
 */
class submission_page implements renderable, templatable {

    protected array $data;

    /**
     * Constructor.
     *
     * @param array $data Prepared submission page data
     */
    public function __construct(array $data) {
        $this->data = $data;
    }

    /**
     * Export data for Mustache template.
     */
    public function export_for_template(renderer_base $output): array {

        $modules = [];

        foreach ($this->data['modules'] as $number => $cmid) {
            $modules[] = [
                'number'    => $number,
                'completed' => !empty($this->data['completion'][$number]),
            ];
        }

        return [
            'previewurl' => $this->data['previewurl'] ?? null,

            'modules' => $modules,

            'allcomplete'            => (bool)($this->data['allcomplete'] ?? false),
            'integritycheckenabled'  => (bool)($this->data['integritycheckenabled'] ?? false),
            'submitenabled'          => (bool)($this->data['submitenabled'] ?? false),
        ];
    }
}
