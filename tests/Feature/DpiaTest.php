<?php

use Illuminate\Support\Facades\Artisan;
use JeffersonGoncalves\Gdpr\Enums\LegalBasis;
use JeffersonGoncalves\GdprDpia\Enums\AssessmentStatus;
use JeffersonGoncalves\GdprDpia\Enums\RiskLevel;
use JeffersonGoncalves\GdprDpia\Enums\Threat;
use JeffersonGoncalves\GdprDpia\Facades\GdprDpia;
use JeffersonGoncalves\GdprDpia\Models\ImpactAssessment;
use JeffersonGoncalves\GdprDpia\Tests\Fixtures\Applicant;
use JeffersonGoncalves\GdprDpia\Tests\Fixtures\Customer;
use JeffersonGoncalves\GdprDpia\Tests\Fixtures\Patient;
use JeffersonGoncalves\GdprRecords\Models\ProcessingActivity;

function activity(string $name, LegalBasis $basis, array $models): ProcessingActivity
{
    return ProcessingActivity::create(['name' => $name, 'purpose' => $name, 'legal_basis' => $basis, 'models' => $models]);
}

function assessment(?ProcessingActivity $activity = null): ImpactAssessment
{
    return ImpactAssessment::create([
        'title' => 'Telemedicine',
        'processing_activity_id' => $activity?->getKey(),
        'risks' => [
            ['title' => 'Diagnosis leak', 'threat' => 'data_disclosure', 'likelihood' => '2', 'impact' => '3', 'measures' => 'Encryption at rest', 'residual_likelihood' => '1', 'residual_impact' => '3'],
            ['title' => 'Re-identification in reports', 'threat' => 'identifying', 'likelihood' => 1, 'impact' => 2],
        ],
    ]);
}

it('scores risks by likelihood and impact, before and after measures', function () {
    $risks = assessment()->scoredRisks();

    expect($risks[0])
        ->threat->toBe(Threat::DataDisclosure)
        ->level->toBe(RiskLevel::High)
        ->residual_level->toBe(RiskLevel::Medium)
        ->and($risks[1]['residual_level'])->toBe(RiskLevel::Low)
        ->and(assessment()->highestRisk(residual: false))->toBe(RiskLevel::High)
        ->and(assessment()->highestRisk())->toBe(RiskLevel::Medium)
        ->and(RiskLevel::score(3, 3))->toBe(RiskLevel::High)
        ->and(fn () => RiskLevel::score(4, 1))->toThrow(InvalidArgumentException::class);
});

it('approves, schedules a review and returns to draft when edited', function () {
    $dpia = assessment()->approve('Anna Schmidt (DPO)');

    expect($dpia->status)->toBe(AssessmentStatus::Approved)
        ->and($dpia->next_review_at->toDateString())->toBe(now()->addYear()->toDateString());

    $this->travel(13)->months();

    expect($dpia->isReviewDue())->toBeTrue()
        ->and(ImpactAssessment::query()->scopes('dueForReview')->count())->toBe(1);

    $dpia->update(['necessity' => 'Only the data needed for the appointment']);

    expect($dpia->fresh())
        ->status->toBe(AssessmentStatus::Draft)
        ->approved_by->toBeNull()
        ->isReviewDue()->toBeFalse();
});

it('needs risks before approval', function () {
    ImpactAssessment::create(['title' => 'Empty'])->approve('DPO');
})->throws(LogicException::class, 'at least one risk');

it('lists activities that may need an assessment', function () {
    $health = activity('Telemedicine', LegalBasis::Consent, [Patient::class]);
    activity('Billing', LegalBasis::Contract, [Customer::class]);
    activity('Background checks', LegalBasis::LegalObligation, [Applicant::class]);

    expect(collect(GdprDpia::candidates())->map(fn (array $c) => [$c['activity']->name, $c['reasons']])->all())->toBe([
        ['Background checks', ['criminal_data']],
        ['Telemedicine', ['special_categories']],
    ]);

    assessment($health);

    expect(GdprDpia::candidates())->toHaveCount(1);
});

it('reports assessments and candidates', function () {
    assessment(activity('Telemedicine', LegalBasis::Consent, [Patient::class]))->approve('DPO');
    activity('Background checks', LegalBasis::LegalObligation, [Applicant::class]);

    Artisan::call('gdpr:dpia');
    expect(Artisan::output())->toContain('Telemedicine')->toContain('high (medium)')->toContain('No DPIA for [Background checks]: criminal_data');

    Artisan::call('gdpr:dpia', ['--json' => true]);
    expect(json_decode(Artisan::output(), true)[0])
        ->processing_activity->toBe('Telemedicine')
        ->highest_residual_risk->toBe('medium')
        ->requires_prior_consultation->toBeFalse()
        ->risks->{0}->threat->toBe('data_disclosure');
});

it('flags a high residual risk for prior consultation', function () {
    $dpia = ImpactAssessment::create(['title' => 'Biometric access', 'risks' => [
        ['title' => 'Template leak', 'likelihood' => 3, 'impact' => 3, 'residual_likelihood' => 2, 'residual_impact' => 3],
    ]]);

    expect($dpia->requiresPriorConsultation())->toBeTrue();

    $dpia->update(['risks' => [['title' => 'Template leak', 'likelihood' => 3, 'impact' => 3, 'residual_likelihood' => 1, 'residual_impact' => 3]]]);

    expect($dpia->fresh()->requiresPriorConsultation())->toBeFalse();
});
