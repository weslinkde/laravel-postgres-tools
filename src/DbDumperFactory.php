<?php

namespace Weslinkde\PostgresTools;

use Spatie\DbDumper\DbDumper;

class DbDumperFactory extends \Spatie\DbSnapshots\DbDumperFactory
{
    public static function createForConnection(string $connectionName): DbDumper
    {
        $dbDumper = parent::createForConnection($connectionName);
        $dbDumper->addExtraOption(config('postgres-tools.addExtraOption', []));

        return $dbDumper;
    }
}
