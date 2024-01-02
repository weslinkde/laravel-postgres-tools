<?php

namespace Weslinkde\PostgresTools\Exceptions;

use Exception;

class CannotCreateConnection extends Exception
{
    public static function connectionDoesNotExist(string $connectionName): static
    {
        return new static("Cannot create a dumper. Connection `{$connectionName}` does not exist.");
    }
}
