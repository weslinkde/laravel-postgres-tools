<?php

namespace Weslinkde\PostgresTools\Exceptions;

use Exception;

class CannotCreateConnection extends Exception
{
    public static function driverNotSupported(string $driver): self
    {
        return new self("Cannot create connection. Driver `{$driver}` is not supported. Only `pgsql` is supported.");
    }

    public static function connectionDoesNotExist(string $connectionName): self
    {
        return new self("Cannot create a dumper. Connection `{$connectionName}` does not exist.");
    }
}
