<?php

namespace Weslinkde\PostgresTools\Commands;

use Illuminate\Console\Command;
use Weslinkde\PostgresTools\Exceptions\CannotCreateConnection;
use Weslinkde\PostgresTools\Support\PostgresHelper;

use function Laravel\Prompts\spin;

class DatabaseVacuum extends Command
{
    protected $signature = 'weslink:database:vacuum {--connection=} {--database=} {--table=*}';

    protected $description = 'Run VACUUM ANALYZE to optimize database performance.';

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

        /** @var array<string> $tables */
        $tables = $this->option('table');
        $tables = empty($tables) ? null : $tables;

        $target = $tables ? implode(', ', $tables) : 'all tables';

        $process = spin(
            fn (): \Symfony\Component\Process\Process => $postgresHelper->vacuumAnalyze($tables),
            "Running VACUUM ANALYZE on {$target}..."
        );

        if ($process->isSuccessful()) {
            $this->info("VACUUM ANALYZE completed successfully on {$target}.");
        } else {
            $this->error('VACUUM ANALYZE failed: '.$process->getErrorOutput());
        }
    }
}
