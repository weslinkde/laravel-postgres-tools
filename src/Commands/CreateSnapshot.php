<?php

namespace Weslinkde\PostgresTools\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Weslinkde\PostgresTools\Exceptions\CannotCreateConnection;
use Weslinkde\PostgresTools\Snapshot;
use Weslinkde\PostgresTools\Support\Format;
use Weslinkde\PostgresTools\Support\PostgresHelper;

use function Laravel\Prompts\error;
use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;

class CreateSnapshot extends Command
{
    protected $signature = 'weslink:snapshot:create {name?} {--connection=} {--database=} {--compress} {--table=*} {--exclude=*} {--exclude-table-data=*}';

    protected $description = 'Create a new snapshot.';

    public function handle(): void
    {
        $connectionName = $this->option('connection')
            ?: config('postgres-tools.default_connection', config('database.default'));

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

        $excludeTableData = $this->option('exclude-table-data') ?: config('postgres-tools.exclude-table-data', null);
        $excludeTableData = is_string($excludeTableData) ? explode(',', $excludeTableData) : $excludeTableData;
        $excludeTableData = empty($excludeTableData) ? null : $excludeTableData;

        $postgresHelper = PostgresHelper::createForConnection($connectionName);

        if ($database = $this->option('database')) {
            $postgresHelper->setName($database);
        }

        /** @var Snapshot $snapshot */
        $snapshot = spin(
            fn () => $postgresHelper->createSnapshot(
                snapshotName: $snapshotName,
                tables: $tables,
                exclude: $exclude,
                excludeTableData: $excludeTableData
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
