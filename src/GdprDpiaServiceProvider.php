<?php

namespace JeffersonGoncalves\GdprDpia;

use JeffersonGoncalves\GdprDpia\Commands\DpiaCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class GdprDpiaServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('gdpr-dpia')
            ->hasConfigFile()
            ->hasMigrations(['create_gdpr_impact_assessments_table'])
            ->hasCommand(DpiaCommand::class);
    }

    public function packageRegistered(): void
    {
        $this->app->singleton(DpiaManager::class);
    }
}
