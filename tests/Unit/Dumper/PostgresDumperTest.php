<?php

use Weslinkde\PostgresTools\Dumper\PostgresDumper;

function dumper(): PostgresDumper
{
    return PostgresDumper::create()
        ->setDbName('app')
        ->setUserName('sail')
        ->setPassword('password')
        ->setHost('127.0.0.1')
        ->setPort(5432);
}

it('builds the dump command as an argument list', function (): void {
    $command = dumper()->getDumpCommand('/tmp/dump.sql');

    expect($command)->toBeArray()
        ->and($command[0])->toBe('pg_dump')
        ->and($command)->toContain('--username', 'sail', '--host', '127.0.0.1', '--port', '5432');
});

it('writes to a file instead of redirecting stdout', function (): void {
    // A `>` redirect needs a shell, and pg_dump cannot record data offsets when its
    // output is not seekable - which is what pg_restore --jobs needs.
    $command = dumper()->getDumpCommand('/tmp/dump.sql');

    expect($command)->toContain('--file', '/tmp/dump.sql')
        ->and(implode(' ', $command))->not->toContain('>');
});

it('keeps shell metacharacters in table names as a single argument', function (): void {
    $malicious = 'users; touch /tmp/pwned';

    $command = dumper()->includeTables([$malicious])->getDumpCommand('/tmp/dump.sql');

    // The table name must survive as one argv entry, never as shell syntax.
    expect($command)->toContain('--table', $malicious);

    $index = array_search('--table', $command, true);
    expect($command[$index + 1])->toBe($malicious);
});

it('keeps shell metacharacters in excluded table names as a single argument', function (): void {
    $malicious = 'logs; rm -rf /';

    $command = dumper()->excludeTables([$malicious])->getDumpCommand('/tmp/dump.sql');

    $index = array_search('--exclude-table', $command, true);
    expect($command[$index + 1])->toBe($malicious);
});

it('escapes arguments instead of running them through a shell', function (): void {
    $dumper = dumper()->includeTables(['users; touch /tmp/pwned']);
    $dumper->setTempFileHandle(tmpfile());

    $commandLine = $dumper->getProcess('/tmp/dump.sql')->getCommandLine();

    // Symfony escapes every argument; the semicolon is inside quotes, not a separator.
    expect($commandLine)->toContain("'users; touch /tmp/pwned'");
});

it('passes the binary path through to the command', function (): void {
    $command = dumper()->setDumpBinaryPath('/opt/homebrew/opt/postgresql@16/bin')
        ->getDumpCommand('/tmp/dump.sql');

    expect($command[0])->toBe('/opt/homebrew/opt/postgresql@16/bin/pg_dump');
});

it('keeps each extra option as its own argument', function (): void {
    $dumper = dumper();

    foreach (['--no-owner', '-Z', '3', '-Fc'] as $option) {
        $dumper->addExtraOption($option);
    }

    expect($dumper->getDumpCommand('/tmp/dump.sql'))->toContain('--no-owner', '-Z', '3', '-Fc');
});
