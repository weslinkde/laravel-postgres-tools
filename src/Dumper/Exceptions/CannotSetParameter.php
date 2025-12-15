<?php

namespace Weslinkde\PostgresTools\Dumper\Exceptions;

use Exception;

class CannotSetParameter extends Exception
{
    public static function conflictingParameters(string $name, string $conflictName): self
    {
        return new self("Cannot set `{$name}` because it conflicts with parameter `{$conflictName}`.");
    }
}
