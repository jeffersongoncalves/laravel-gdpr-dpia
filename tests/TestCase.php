<?php

namespace JeffersonGoncalves\GdprDpia\Tests;

use JeffersonGoncalves\GdprDpia\GdprDpiaServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            GdprDpiaServiceProvider::class,
        ];
    }
}
