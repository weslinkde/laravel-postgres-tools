<?php

namespace Weslinkde\PostgresTools;

use Illuminate\Contracts\Filesystem\Factory;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Weslinkde\PostgresTools\Commands\CloneDatabase;
use Weslinkde\PostgresTools\Commands\CreateDatabase;
use Weslinkde\PostgresTools\Commands\CreateSnapshot;
use Weslinkde\PostgresTools\Commands\DeleteSnapshot;
use Weslinkde\PostgresTools\Commands\DropDatabase;
use Weslinkde\PostgresTools\Commands\LoadSnapshot;

class PostgresToolsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/weslinkde/laravel-package-tools
         */
        $package
            ->name('laravel-postgres-tools')
            ->hasConfigFile()
            ->hasCommands([
                'command.weslink.snapshot:create',
                'command.weslink.snapshot:load',
                'command.weslink.snapshot:delete',
                'command.weslink.database:create',
                'command.weslink.database:drop',
                'command.weslink.database:clone',
            ]);
    }

    public function bootingPackage()
    {
        $this->app->bind(PostgresSnapshotRepository::class, function () {
            $diskName = config('postgres-tools.disk');

            $disk = app(Factory::class)->disk($diskName);

            return new PostgresSnapshotRepository($disk);
        });

        $this->app->bind('command.weslink.snapshot:create', CreateSnapshot::class);
        $this->app->bind('command.weslink.snapshot:load', LoadSnapshot::class);
        $this->app->bind('command.weslink.snapshot:delete', DeleteSnapshot::class);
        $this->app->bind('command.weslink.database:create', CreateDatabase::class);
        $this->app->bind('command.weslink.database:drop', DropDatabase::class);
        $this->app->bind('command.weslink.database:clone', CloneDatabase::class);
    }
}
