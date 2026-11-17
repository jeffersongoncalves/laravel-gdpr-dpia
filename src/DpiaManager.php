<?php

namespace JeffersonGoncalves\GdprDpia;

use JeffersonGoncalves\Gdpr\Enums\DataCategory;
use JeffersonGoncalves\Gdpr\Gdpr;
use JeffersonGoncalves\GdprDpia\Models\ImpactAssessment;
use JeffersonGoncalves\GdprRecords\Models\ProcessingActivity;

class DpiaManager
{
    public function __construct(protected Gdpr $gdpr) {}

    /**
     * Processing activities with no assessment that likely need one: processing of special categories
     * (Art. 9) or criminal data (Art. 10) on a large scale is a case of Art. 35(3)(b). Profiling with
     * significant effects and systematic monitoring of public areas (Art. 35(3)(a) and (c)) still need your judgment.
     *
     * @return list<array{activity: ProcessingActivity, reasons: list<string>}>
     */
    public function candidates(): array
    {
        $inventory = collect($this->gdpr->inventory());
        $specialModels = $inventory->where('category', DataCategory::Special->value)->pluck('model')->unique()->all();
        $criminalModels = $inventory->where('category', DataCategory::Criminal->value)->pluck('model')->unique()->all();

        $assessed = ImpactAssessment::query()->whereNotNull('processing_activity_id')->pluck('processing_activity_id')->all();

        $candidates = [];

        foreach (ProcessingActivity::query()->whereNotIn('id', $assessed)->orderBy('name')->get() as $activity) {
            $reasons = [];

            if (array_intersect($activity->models ?? [], $specialModels) !== []) {
                $reasons[] = 'special_categories';
            }

            if (array_intersect($activity->models ?? [], $criminalModels) !== []) {
                $reasons[] = 'criminal_data';
            }

            if ($reasons !== []) {
                $candidates[] = ['activity' => $activity, 'reasons' => $reasons];
            }
        }

        return $candidates;
    }

    /**
     * @return array<string, mixed>
     */
    public function export(ImpactAssessment $assessment): array
    {
        return [
            'title' => $assessment->title,
            'processing_activity' => $assessment->processingActivity?->name,
            'description' => $assessment->description,
            'necessity' => $assessment->necessity,
            'risks' => array_map(fn (array $risk): array => [
                ...$risk,
                'threat' => $risk['threat']?->value,
                'level' => $risk['level']->value,
                'residual_level' => $risk['residual_level']->value,
            ], $assessment->scoredRisks()),
            'highest_residual_risk' => $assessment->highestRisk()?->value,
            'requires_prior_consultation' => $assessment->requiresPriorConsultation(),
            'dpo_opinion' => $assessment->dpo_opinion,
            'status' => $assessment->status->value,
            'approved_at' => $assessment->approved_at?->toIso8601String(),
            'approved_by' => $assessment->approved_by,
            'next_review_at' => $assessment->next_review_at?->toIso8601String(),
        ];
    }
}
