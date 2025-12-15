<?php

namespace Weslinkde\PostgresTools;

use Weslinkde\PostgresTools\Dumper\PostgresDumper;

class DbDumperFactory
{
    /**
     * Create a PostgreSQL dumper for the given connection.
     */
    public static function createForConnection(string $connectionName): PostgresDumper
    {
        $dbConfig = config("database.connections.{$connectionName}");

        if (is_null($dbConfig)) {
            throw new \RuntimeException("Connection [{$connectionName}] does not exist.");
        }

        if ($dbConfig['driver'] !== 'pgsql') {
            throw new \RuntimeException("Driver [{$dbConfig['driver']}] is not supported. Only PostgreSQL is supported.");
        }

        $fallback = $dbConfig['read']['host'] ?? $dbConfig['host'];
        $dbHost = $dbConfig['read']['host'][0] ?? $fallback;
        $dbName = $dbConfig['connect_via_database'] ?? $dbConfig['database'];

        $dbDumper = PostgresDumper::create()
            ->setHost($dbHost ?? '')
            ->setDbName($dbName)
            ->setUserName($dbConfig['username'] ?? '')
            ->setPassword($dbConfig['password'] ?? '');

        if (isset($dbConfig['port'])) {
            $dbDumper->setPort($dbConfig['port']);
        }

        $extraOptionsString = config('postgres-tools.addExtraOption', '');
        if (! empty($extraOptionsString)) {
            $extraOptions = array_filter(explode(' ', (string) $extraOptionsString));
            foreach ($extraOptions as $option) {
                $dbDumper->addExtraOption($option);
            }
        }

        return $dbDumper;
    }
}
