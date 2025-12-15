<?php

namespace Weslinkde\PostgresTools\Events;

use Weslinkde\PostgresTools\Snapshot;

class CreatedSnapshot
{
    public function __construct(
        public Snapshot $snapshot
    ) {
        //
    }
}
