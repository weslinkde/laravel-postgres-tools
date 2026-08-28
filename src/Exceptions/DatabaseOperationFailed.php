<?php

namespace Weslinkde\PostgresTools\Exceptions;

use Symfony\Component\Process\Process;

class DatabaseOperationFailed extends ProcessFailed
{
    public static function couldNotCreateDatabase(string $databaseName, Process $process): self
    {
        return self::forProcess("The database `{$databaseName}` could not be created.", $process);
    }

    public static function couldNotDropDatabase(string $databaseName, Process $process): self
    {
        return self::forProcess("The database `{$databaseName}` could not be dropped.", $process);
    }

    public static function queryFailed(string $description, Process $process): self
    {
        return self::forProcess("The query to {$description} failed.", $process);
    }
}
