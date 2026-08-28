<?php

use Illuminate\Filesystem\FilesystemAdapter;
use Weslinkde\PostgresTools\Snapshot;

/**
 * Exposes the protected streaming helper so the truncation guard can be tested.
 */
final class StreamableSnapshot extends Snapshot
{
    public function streamTo(string $localFilePath): void
    {
        $this->streamToLocalFile($this->disk, $this->fileName, $localFilePath);
    }
}

beforeEach(function (): void {
    $this->tempDirectory = sys_get_temp_dir().'/postgres-tools-'.uniqid();
    mkdir($this->tempDirectory);
});

afterEach(function (): void {
    foreach (glob($this->tempDirectory.'/*') ?: [] as $file) {
        unlink($file);
    }

    rmdir($this->tempDirectory);
});

function diskReturning(string $contents, int $reportedSize): FilesystemAdapter
{
    $stream = fopen('php://memory', 'r+');
    fwrite($stream, $contents);
    rewind($stream);

    $disk = Mockery::mock(FilesystemAdapter::class);
    $disk->shouldReceive('size')->with('snapshot.sql')->andReturn($reportedSize);
    $disk->shouldReceive('readStream')->with('snapshot.sql')->andReturn($stream);

    return $disk;
}

it('streams a snapshot from a remote disk to a local file', function (): void {
    $contents = str_repeat('dump-payload', 2048);

    $snapshot = new StreamableSnapshot(diskReturning($contents, strlen($contents)), 'snapshot.sql');
    $target = $this->tempDirectory.'/out.sql';

    $snapshot->streamTo($target);

    expect(file_get_contents($target))->toBe($contents);
});

it('rejects a snapshot that arrived incompletely', function (): void {
    // The disk reports a bigger file than the stream delivers - a download that broke
    // off half way, which would otherwise pass the preflight and fail after the drop.
    $snapshot = new StreamableSnapshot(diskReturning('truncated', 9999), 'snapshot.sql');

    $snapshot->streamTo($this->tempDirectory.'/out.sql');
})->throws(Exception::class, 'streamed incompletely');
