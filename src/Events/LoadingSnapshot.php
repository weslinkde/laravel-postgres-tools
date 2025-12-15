<?php

namespace Weslinkde\PostgresTools\Events;

use Weslinkde\PostgresTools\Snapshot;

class LoadingSnapshot
{
    public function __construct(
        public Snapshot $snapshot
    ) {
        //
    }
}
