<?php

namespace Weslinkde\PostgresTools\Events;

use Illuminate\Filesystem\FilesystemAdapter;

class DeletedSnapshot
{
    public function __construct(
        public string $fileName,
        public FilesystemAdapter $disk
    ) {
        //
    }
}
