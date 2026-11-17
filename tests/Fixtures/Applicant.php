<?php

namespace JeffersonGoncalves\GdprDpia\Tests\Fixtures;

use Illuminate\Database\Eloquent\Model;
use JeffersonGoncalves\Gdpr\Attributes\PersonalData;
use JeffersonGoncalves\Gdpr\Concerns\InteractsWithPersonalData;
use JeffersonGoncalves\Gdpr\Contracts\HasPersonalData;
use JeffersonGoncalves\Gdpr\Enums\DataCategory;

#[PersonalData('name')]
#[PersonalData('convictions', category: DataCategory::Criminal)]
class Applicant extends Model implements HasPersonalData
{
    use InteractsWithPersonalData;

    protected $table = 'applicants';
}
