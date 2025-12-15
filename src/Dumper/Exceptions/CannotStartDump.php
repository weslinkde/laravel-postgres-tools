<?php

namespace Weslinkde\PostgresTools\Dumper\Exceptions;

use Exception;

class CannotStartDump extends Exception
{
    public static function emptyParameter(string $name): self
    {
        return new self("Parameter `{$name}` cannot be empty.");
    }
}
