<?php

namespace JeffersonGoncalves\GdprDpia\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JeffersonGoncalves\GdprDpia\GdprDpia
 */
class GdprDpia extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'laravel-gdpr-dpia';
    }
}
