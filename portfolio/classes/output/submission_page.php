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

    /** @var array */
    protected $data;

    /**
     * Constructor.
     *
     * @param array $data Prepared submission page data
     */
    public function __construct(array $data) {
        $this->data = $data;
    }

    /**
     * Export data for the Mustache template.
     *
     * @param renderer_base $output
     * @return array
     */
    public function export_for_template(renderer_base $output): array {

        $modules = [];

        // Build module list in template-friendly format.
        if (!empty($this->data['modules'])) {
            foreach ($this->data['modules'] as $moduleid) {
                $modules[] = [
                    'id' => $moduleid,
                    'completed' => !empty($this->data['completion'][$moduleid]),
                ];
            }
        }

        // Determine submit vs resubmit state.
        // For now, default to first submission.
        // Later we will inspect assignment attempt history.
        $issubmit = true;
        $isresubmit = false;

        return [
            'previewurl' => $this->data['previewurl'] ?? null,

            'modules' => $modules,

            'allcomplete' => !empty($this->data['allcomplete']),
            'integritycheckenabled' => !empty($this->data['integritycheckenabled']),
            'submitenabled' => !empty($this->data['submitenabled']),

            'issubmit' => $issubmit,
            'isresubmit' => $isresubmit,

            'submittype' => $issubmit ? 'submit' : 'resubmit',
        ];
    }
}
