<?php

namespace JeffersonGoncalves\GdprDpia\Tests;

use JeffersonGoncalves\Gdpr\GdprServiceProvider;
use JeffersonGoncalves\GdprDpia\GdprDpiaServiceProvider;
use JeffersonGoncalves\GdprDpia\Tests\Fixtures\Applicant;
use JeffersonGoncalves\GdprDpia\Tests\Fixtures\Customer;
use JeffersonGoncalves\GdprDpia\Tests\Fixtures\Patient;
use JeffersonGoncalves\GdprRecords\GdprRecordsServiceProvider;
use JeffersonGoncalves\PestPluginDatabaseMigrations\LoadsPackageMigrations;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    use LoadsPackageMigrations;

    protected function getPackageProviders($app): array
    {
        return [
            GdprServiceProvider::class,
            GdprRecordsServiceProvider::class,
            GdprDpiaServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('gdpr.models', [Customer::class, Patient::class, Applicant::class]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadPackageMigrations(GdprRecordsServiceProvider::class);
        $this->loadPackageMigrations(GdprDpiaServiceProvider::class);
    }
}
