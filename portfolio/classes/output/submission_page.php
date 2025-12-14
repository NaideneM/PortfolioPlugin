<?php

namespace assignsubmission_portfolio\output;

defined('MOODLE_INTERNAL') || die();

use renderable;
use templatable;
use renderer_base;

/**
 * Renderable class for the Portfolio Submission page.
 */
class submission_page implements renderable, templatable {

    protected array $data;

    public function __construct(array $data) {
        $this->data = $data;
    }

    public function export_for_template(renderer_base $output): array {

        $modules = [];

        foreach ($this->data['modules'] as $number => $cmid) {
            $modules[] = [
                'number' => $number,
                'completed' => !empty($this->data['completion'][$number]),
            ];
        }

        // Placeholder logic — later tied to attempt history.
        $issubmit = true;
        $isresubmit = false;

        return [
            'previewurl' => $this->data['previewurl'] ?? null,

            'modules' => $modules,

            'allcomplete' => (bool)$this->data['allcomplete'],
            'integritycheckenabled' => (bool)$this->data['integritycheckenabled'],
            'submitenabled' => (bool)$this->data['submitenabled'],

            'issubmit' => $issubmit,
            'isresubmit' => $isresubmit,
        ];
    }
}
