<?php

namespace Weslinkde\PostgresTools\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Spatie\DbSnapshots\Helpers\Format;
use Spatie\DbSnapshots\Snapshot;
use Weslinkde\PostgresTools\Exceptions\CannotCreateConnection;
use Weslinkde\PostgresTools\Support\PostgresHelper;

use function Laravel\Prompts\error;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;

class CreateSnapshot extends Command
{
    protected $signature = 'weslink:snapshot:create {name?} {--connection=} {--compress} {--table=*} {--exclude=*}';

    protected $description = 'Create a new snapshot.';

    public function handle()
    {
        $connectionName = $this->option('connection')
            ?: config('postgres-tools.default_connection')
            ?? config('database.default');

        try {
            PostgresHelper::createForConnection($connectionName);
        } catch (CannotCreateConnection $e) {
            error($e->getMessage());

            return;
        }

        $snapshotName = $this->argument('name') ?? Carbon::now()->format('Y-m-d_H-i-s');

        $tables = $this->option('table') ?: config('postgres-tools.tables', null);
        $tables = is_string($tables) ? explode(',', $tables) : $tables;

        if (is_null($tables)) {
            $exclude = $this->option('exclude') ?: config('postgres-tools.exclude', null);
            $exclude = is_string($exclude) ? explode(',', $exclude) : $exclude;
        } else {
            $exclude = null;
        }

        $postgresHelper = PostgresHelper::createForConnection($connectionName);

        /** @var Snapshot $snapshot */
        $snapshot = spin(
            fn () => $postgresHelper->createSnapshot(
                snapshotName: $snapshotName,
                tables: $tables,
                exclude: $exclude
            ),
            'Creating new snapshot...'
        );

        $size = Format::humanReadableSize($snapshot->size());

        table(
            ['Name', 'Size', 'Path'],
            [
                [$snapshotName, $size, $snapshot->disk->path($snapshot->fileName)],
            ]
        );

    }
}
