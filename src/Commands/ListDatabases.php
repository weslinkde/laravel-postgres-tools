<?php

namespace Weslinkde\PostgresTools\Commands;

use Illuminate\Console\Command;
use Weslinkde\PostgresTools\Exceptions\CannotCreateConnection;
use Weslinkde\PostgresTools\Support\Format;
use Weslinkde\PostgresTools\Support\PostgresHelper;

use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;

class ListDatabases extends Command
{
    protected $signature = 'weslink:database:list {--connection=}';

    protected $description = 'List all PostgreSQL databases.';

    public function handle(): void
    {
        $connectionName = $this->option('connection')
            ?: config('postgres-tools.default_connection', config('database.default'));

        try {
            $postgresHelper = PostgresHelper::createForConnection($connectionName);
        } catch (CannotCreateConnection $e) {
            $this->error($e->getMessage());

            return;
        }

        /** @var array<int, array{name: string, owner: string, size: int}> $databases */
        $databases = spin(
            fn (): array => $postgresHelper->listDatabases(),
            'Fetching databases...'
        );

        if (empty($databases)) {
            $this->warn('No databases found.');

            return;
        }

        $rows = array_map(fn (array $db): array => [
            $db['name'],
            $db['owner'],
            Format::humanReadableSize($db['size']),
        ], $databases);

        table(['Name', 'Owner', 'Size'], $rows);
    }
}
