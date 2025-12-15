<?php

namespace Weslinkde\PostgresTools\Events;

use Weslinkde\PostgresTools\Snapshot;

class DeletingSnapshot
{
    public function __construct(
        public Snapshot $snapshot
    ) {
        //
    }
}
