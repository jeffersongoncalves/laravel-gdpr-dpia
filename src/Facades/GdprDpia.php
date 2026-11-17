<?php

namespace JeffersonGoncalves\GdprDpia\Facades;

use Illuminate\Support\Facades\Facade;
use JeffersonGoncalves\GdprDpia\DpiaManager;

/**
 * @method static list<array{activity: \JeffersonGoncalves\GdprRecords\Models\ProcessingActivity, reasons: list<string>}> candidates()
 * @method static array<string, mixed> export(\JeffersonGoncalves\GdprDpia\Models\ImpactAssessment $assessment)
 *
 * @see DpiaManager
 */
class GdprDpia extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return DpiaManager::class;
    }
}
