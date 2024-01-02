<?php

namespace Weslinkde\PostgresTools;

use Spatie\DbSnapshots\Events\LoadedSnapshot;
use Spatie\DbSnapshots\Events\LoadingSnapshot;
use Spatie\DbSnapshots\Snapshot;
use Weslinkde\PostgresTools\Support\PostgresHelper;

use function Laravel\Prompts\spin;

class PostgresSnapshot extends Snapshot
{
    public function load(?string $connectionName = null, bool $dropTables = true): void
    {
        event(new LoadingSnapshot($this));

        if (! $connectionName) {
            $connectionName = config('database.default');
        }

        if ($dropTables) {
            $this->dropAllCurrentTables();
        }

        $postgresHelper = PostgresHelper::createForConnection($connectionName);
        $dbDumpFilePath = $this->disk->path($this->fileName);

        spin(
            fn () => $postgresHelper->restoreSnapshot($dbDumpFilePath),
            'Importing snapshot '.$this->name.'...'
        );

        event(new LoadedSnapshot($this));
    }
}
