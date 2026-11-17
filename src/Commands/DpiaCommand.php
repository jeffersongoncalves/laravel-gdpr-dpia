<?php

namespace JeffersonGoncalves\GdprDpia\Commands;

use Illuminate\Console\Command;
use JeffersonGoncalves\GdprDpia\DpiaManager;
use JeffersonGoncalves\GdprDpia\Models\ImpactAssessment;

class DpiaCommand extends Command
{
    protected $signature = 'gdpr:dpia {--json : Output every assessment as JSON}';

    protected $description = 'List data protection impact assessments (DPIA) and the processing activities that may still need one';

    public function handle(DpiaManager $dpia): int
    {
        $assessments = ImpactAssessment::query()->with('processingActivity')->orderBy('title')->get();

        if ($this->option('json')) {
            $this->line((string) json_encode($assessments->map(fn (ImpactAssessment $a): array => $dpia->export($a))->all(), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));

            return self::SUCCESS;
        }

        if ($assessments->isNotEmpty()) {
            $this->table(
                ['#', 'Title', 'Activity', 'Status', 'Risks', 'Highest (residual)', 'Next review'],
                $assessments->map(fn (ImpactAssessment $a): array => [
                    $a->getKey(),
                    $a->title,
                    $a->processingActivity->name ?? '-',
                    $a->status->value.($a->isReviewDue() ? ' (review due)' : ''),
                    count($a->scoredRisks()),
                    ($a->highestRisk(false)->value ?? '-').' ('.($a->highestRisk()->value ?? '-').')',
                    $a->next_review_at?->toDateString() ?? '-',
                ])->all(),
            );
        }

        $candidates = $dpia->candidates();

        foreach ($candidates as $candidate) {
            $this->warn("No DPIA for [{$candidate['activity']->name}]: ".implode(', ', $candidate['reasons']));
        }

        if ($assessments->isEmpty() && $candidates === []) {
            $this->info('No assessments, and no processing activity that obviously needs one.');
        }

        return self::SUCCESS;
    }
}
