<?php

namespace Weslinkde\PostgresTools;

use Illuminate\Contracts\Filesystem\Factory;
use Illuminate\Support\ServiceProvider;
use Weslinkde\PostgresTools\Commands\CloneDatabase;
use Weslinkde\PostgresTools\Commands\CreateDatabase;
use Weslinkde\PostgresTools\Commands\CreateSnapshot;
use Weslinkde\PostgresTools\Commands\DatabaseSize;
use Weslinkde\PostgresTools\Commands\DatabaseVacuum;
use Weslinkde\PostgresTools\Commands\DataDump;
use Weslinkde\PostgresTools\Commands\DeleteSnapshot;
use Weslinkde\PostgresTools\Commands\DropDatabase;
use Weslinkde\PostgresTools\Commands\ListDatabases;
use Weslinkde\PostgresTools\Commands\ListSnapshots;
use Weslinkde\PostgresTools\Commands\LoadSnapshot;
use Weslinkde\PostgresTools\Commands\SchemaDump;

class PostgresToolsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/postgres-tools.php', 'postgres-tools');

        $this->app->bind(PostgresSnapshotRepository::class, function (): \Weslinkde\PostgresTools\PostgresSnapshotRepository {
            $diskName = config('postgres-tools.disk');

            return new PostgresSnapshotRepository(app(Factory::class), $diskName);
        });

        $this->app->bind('command.weslink.snapshot:create', CreateSnapshot::class);
        $this->app->bind('command.weslink.snapshot:load', LoadSnapshot::class);
        $this->app->bind('command.weslink.snapshot:delete', DeleteSnapshot::class);
        $this->app->bind('command.weslink.snapshot:list', ListSnapshots::class);
        $this->app->bind('command.weslink.database:create', CreateDatabase::class);
        $this->app->bind('command.weslink.database:drop', DropDatabase::class);
        $this->app->bind('command.weslink.database:clone', CloneDatabase::class);
        $this->app->bind('command.weslink.database:list', ListDatabases::class);
        $this->app->bind('command.weslink.database:size', DatabaseSize::class);
        $this->app->bind('command.weslink.database:vacuum', DatabaseVacuum::class);
        $this->app->bind('command.weslink.schema:dump', SchemaDump::class);
        $this->app->bind('command.weslink.data:dump', DataDump::class);
    }

    public function boot(): void
    {
        $this->ensureSnapshotsDiskExists();

        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/../config/postgres-tools.php' => config_path('postgres-tools.php'),
            ], 'postgres-tools-config');

            $this->commands([
                CreateSnapshot::class,
                LoadSnapshot::class,
                DeleteSnapshot::class,
                ListSnapshots::class,
                CreateDatabase::class,
                DropDatabase::class,
                CloneDatabase::class,
                ListDatabases::class,
                DatabaseSize::class,
                DatabaseVacuum::class,
                SchemaDump::class,
                DataDump::class,
            ]);
        }
    }

    /**
     * Ensure the snapshots disk exists in the filesystem config.
     * This is useful for development/testing when the disk isn't configured.
     */
    protected function ensureSnapshotsDiskExists(): void
    {
        $diskName = config('postgres-tools.disk', 'snapshots');

        if (! config("filesystems.disks.{$diskName}")) {
            config([
                "filesystems.disks.{$diskName}" => [
                    'driver' => 'local',
                    'root' => storage_path("app/{$diskName}"),
                ],
            ]);
        }
    }
}
