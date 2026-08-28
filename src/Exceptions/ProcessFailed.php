<?php

namespace Weslinkde\PostgresTools\Exceptions;

use Exception;
use Symfony\Component\Process\Process;
use Throwable;

class ProcessFailed extends Exception
{
    /**
     * Final so the named constructors below can safely instantiate subclasses.
     */
    final public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }

    public static function forProcess(string $description, Process $process): static
    {
        return new static($description.self::formatProcessOutput($process));
    }

    protected static function formatProcessOutput(Process $process): string
    {
        $output = $process->getOutput() ?: '<no output>';
        $errorOutput = $process->getErrorOutput() ?: '<no output>';
        $exitCodeText = $process->getExitCodeText() ?: '<no exit text>';

        return <<<CONSOLE

            Exitcode
            ========
            {$process->getExitCode()}: {$exitCodeText}

            Output
            ======
            {$output}

            Error Output
            ============
            {$errorOutput}
            CONSOLE;
    }
}
