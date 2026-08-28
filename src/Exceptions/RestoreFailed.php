<?php

namespace Weslinkde\PostgresTools\Exceptions;

use Symfony\Component\Process\Process;

class RestoreFailed extends ProcessFailed
{
    public static function processDidNotEndSuccessfully(Process $process): self
    {
        return self::forProcess('The restore process failed with a none successful exitcode.', $process);
    }

    public static function snapshotFileIsNotReadable(string $filePath): self
    {
        return new self("The snapshot file `{$filePath}` does not exist or is not readable.");
    }

    public static function archiveCannotBeReadByClient(
        string $filePath,
        ?string $archiveVersion,
        string $clientVersion,
        Process $process
    ): self {
        $archive = $archiveVersion === null
            ? 'the file is not a pg_dump archive'
            : "archive format version {$archiveVersion}";

        return self::forProcess(
            "The local PostgreSQL client cannot read the snapshot archive `{$filePath}` "
            ."({$archive}, client: {$clientVersion}). "
            .'The database was left untouched. Install a PostgreSQL client that is at least as new as the '
            .'server the snapshot was dumped from, or point `postgres-tools.bin_path` at one.',
            $process
        );
    }
}
