<?php

namespace Weslinkde\PostgresTools\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Weslinkde\PostgresTools\Exceptions\CannotCreateConnection;
use Weslinkde\PostgresTools\Support\Format;
use Weslinkde\PostgresTools\Support\PostgresHelper;

use function Laravel\Prompts\spin;

class DataDump extends Command
{
    protected $signature = 'weslink:data:dump {name?} {--connection=} {--database=} {--table=*}';

    protected $description = 'Dump only the database data (no schema) - useful for seed files.';

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

        $name = $this->argument('name') ?? Carbon::now()->format('Y-m-d_H-i-s').'_data';
        $fileName = "{$name}.sql";

        $disk = Storage::disk(config('postgres-tools.disk'));
        $outputPath = $disk->path($fileName);

        $target = $tables ? implode(', ', $tables) : 'all tables';

        $process = spin(
            fn (): \Symfony\Component\Process\Process => $postgresHelper->dumpData($outputPath, $tables),
            "Dumping data from {$target}..."
        );

        if ($process->isSuccessful()) {
            $size = Format::humanReadableSize(filesize($outputPath) ?: 0);
            $this->info("Data dump created: {$fileName} ({$size})");
            $this->info("Path: {$outputPath}");

            if ($tables) {
                $this->info('Tables: '.implode(', ', $tables));
            }
        } else {
            $this->error('Data dump failed: '.$process->getErrorOutput());
        }
    }
}
