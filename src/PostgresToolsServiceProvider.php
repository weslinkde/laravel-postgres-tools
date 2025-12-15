<?php

namespace Weslinkde\PostgresTools;

use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Support\ServiceProvider;
use Weslinkde\PostgresTools\Commands\CloneDatabase;
use Weslinkde\PostgresTools\Commands\CreateDatabase;
use Weslinkde\PostgresTools\Commands\CreateSnapshot;
use Weslinkde\PostgresTools\Commands\DeleteSnapshot;
use Weslinkde\PostgresTools\Commands\DropDatabase;
use Weslinkde\PostgresTools\Commands\LoadSnapshot;

class PostgresToolsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/postgres-tools.php', 'postgres-tools');

        $this->app->bind(PostgresSnapshotRepository::class, function () {
            $diskName = config('postgres-tools.disk');

            return new PostgresSnapshotRepository(app(Factory::class), $diskName);
        });

        $this->app->bind('command.weslink.snapshot:create', CreateSnapshot::class);
        $this->app->bind('command.weslink.snapshot:load', LoadSnapshot::class);
        $this->app->bind('command.weslink.snapshot:delete', DeleteSnapshot::class);
        $this->app->bind('command.weslink.database:create', CreateDatabase::class);
        $this->app->bind('command.weslink.database:drop', DropDatabase::class);
        $this->app->bind('command.weslink.database:clone', CloneDatabase::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/postgres-tools.php' => config_path('postgres-tools.php'),
            ], 'postgres-tools-config');

            $this->commands([
                CreateSnapshot::class,
                LoadSnapshot::class,
                DeleteSnapshot::class,
                CreateDatabase::class,
                DropDatabase::class,
                CloneDatabase::class,
            ]);
        }
    }
}
