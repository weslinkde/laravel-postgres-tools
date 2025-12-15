<?php

namespace Weslinkde\PostgresTools\Commands;

use Illuminate\Console\Command;
use Weslinkde\PostgresTools\Exceptions\CannotCreateConnection;
use Weslinkde\PostgresTools\Support\Format;
use Weslinkde\PostgresTools\Support\PostgresHelper;

use function Laravel\Prompts\spin;
use function Laravel\Prompts\table;

class DatabaseSize extends Command
{
    protected $signature = 'weslink:database:size {--connection=} {--database=}';

    protected $description = 'Show database and table sizes.';

    public function handle(): void
    {
        $connectionName = $this->option('connection')
            ?: config('postgres-tools.default_connection', config('database.default'));

        try {
            $postgresHelper = PostgresHelper::createForConnection($connectionName);

            if ($database = $this->option('database')) {
                $postgresHelper->setName($database);
            }
        } catch (CannotCreateConnection $e) {
            $this->error($e->getMessage());

            return;
        }

        /** @var array{database: string, total_size: int, tables: array<int, array{name: string, size: int, rows: int}>} $sizeInfo */
        $sizeInfo = spin(
            fn (): array => $postgresHelper->getDatabaseSize(),
            'Calculating database size...'
        );

        $this->info("Database: {$sizeInfo['database']}");
        $this->info('Total Size: '.Format::humanReadableSize($sizeInfo['total_size']));
        $this->newLine();

        if (empty($sizeInfo['tables'])) {
            $this->warn('No tables found.');

            return;
        }

        $rows = array_map(fn (array $table): array => [
            $table['name'],
            Format::humanReadableSize($table['size']),
            number_format($table['rows']),
        ], $sizeInfo['tables']);

        table(['Table', 'Size', 'Rows'], $rows);
    }
}
