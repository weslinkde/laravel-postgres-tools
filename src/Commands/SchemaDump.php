<?php

namespace Weslinkde\PostgresTools\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Weslinkde\PostgresTools\Exceptions\CannotCreateConnection;
use Weslinkde\PostgresTools\Support\Format;
use Weslinkde\PostgresTools\Support\PostgresHelper;

use function Laravel\Prompts\spin;

class SchemaDump extends Command
{
    protected $signature = 'weslink:schema:dump {name?} {--connection=} {--database=}';

    protected $description = 'Dump only the database schema (no data).';

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

        $name = $this->argument('name') ?? Carbon::now()->format('Y-m-d_H-i-s').'_schema';
        $fileName = "{$name}.sql";

        $disk = Storage::disk(config('postgres-tools.disk'));
        $outputPath = $disk->path($fileName);

        $process = spin(
            fn (): \Symfony\Component\Process\Process => $postgresHelper->dumpSchema($outputPath),
            'Dumping database schema...'
        );

        if ($process->isSuccessful()) {
            $size = Format::humanReadableSize(filesize($outputPath) ?: 0);
            $this->info("Schema dump created: {$fileName} ({$size})");
            $this->info("Path: {$outputPath}");
        } else {
            $this->error('Schema dump failed: '.$process->getErrorOutput());
        }
    }
}
