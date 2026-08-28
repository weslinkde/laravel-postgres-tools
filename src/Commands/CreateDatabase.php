<?php

namespace Weslinkde\PostgresTools\Commands;

use Illuminate\Console\Command;
use Illuminate\Console\ConfirmableTrait;
use Symfony\Component\Process\Process;
use Weslinkde\PostgresTools\Support\PostgresHelper;

use function Laravel\Prompts\error;
use function Laravel\Prompts\spin;

class CreateDatabase extends Command
{
    use ConfirmableTrait;

    protected $signature = 'weslink:database:create {name}';

    protected $description = 'Creates a database.';

    public function handle(): int
    {
        $newDatabaseName = $this->argument('name');

        $connectionName = config('postgres-tools.default_connection', config('database.default'));

        try {
            $postgresHelper = PostgresHelper::createForConnection($connectionName)->setName($newDatabaseName);

            /** @var Process|bool $result */
            $result = spin(fn (): Process|bool => $postgresHelper->createDatabase(), 'Creating new database...');
        } catch (\Exception $e) {
            error($e->getMessage());

            return self::FAILURE;
        }

        if ($result === false) {
            $this->error("Database `{$newDatabaseName}` already exists.");

            return self::FAILURE;
        }

        $this->info("Database with name `{$newDatabaseName}` was created!");

        return self::SUCCESS;
    }
}
