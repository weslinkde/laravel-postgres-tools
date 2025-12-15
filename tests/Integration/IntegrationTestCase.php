<?php

namespace Weslinkde\PostgresTools\Tests\Integration;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Weslinkde\PostgresTools\Support\PostgresHelper;
use Weslinkde\PostgresTools\Tests\TestCase;

abstract class IntegrationTestCase extends TestCase
{
    protected array $testDatabases = [];

    protected array $testSnapshots = [];

    protected function setUp(): void
    {
        parent::setUp();

        if (! $this->isPostgresAvailable()) {
            $this->markTestSkipped('PostgreSQL not available');
        }
    }

    protected function tearDown(): void
    {
        // Cleanup test databases
        foreach ($this->testDatabases as $dbName) {
            $this->dropTestDatabaseIfExists($dbName);
        }
        $this->testDatabases = [];

        // Cleanup test snapshots
        foreach ($this->testSnapshots as $snapshotName) {
            $this->deleteSnapshotIfExists($snapshotName);
        }
        $this->testSnapshots = [];

        parent::tearDown();
    }

    protected function isPostgresAvailable(): bool
    {
        try {
            DB::connection('pgsql')->getPdo();

            return true;
        } catch (\Exception) {
            return false;
        }
    }

    protected function generateTestDatabaseName(string $prefix = 'test'): string
    {
        $name = $prefix.'_'.time().'_'.mt_rand(1000, 9999);
        $this->testDatabases[] = $name;

        return $name;
    }

    protected function createTestDatabase(string $name): void
    {
        PostgresHelper::createForConnection('pgsql')
            ->setName($name)
            ->createDatabase();
    }

    protected function dropTestDatabaseIfExists(string $name): void
    {
        try {
            PostgresHelper::createForConnection('pgsql')
                ->setName($name)
                ->dropDatabase();
        } catch (\Exception) {
            // Ignore if database doesn't exist
        }
    }

    protected function databaseExists(string $name): bool
    {
        $result = DB::connection('pgsql')
            ->select('SELECT 1 FROM pg_database WHERE datname = ?', [$name]);

        return count($result) > 0;
    }

    protected function generateTestSnapshotName(string $prefix = 'test'): string
    {
        $name = $prefix.'_'.time().'_'.mt_rand(1000, 9999);
        $this->testSnapshots[] = $name;

        return $name;
    }

    protected function deleteSnapshotIfExists(string $name): void
    {
        try {
            $disk = Storage::disk('snapshots');
            $files = $disk->files();
            foreach ($files as $file) {
                if (str_starts_with(basename((string) $file), $name)) {
                    $disk->delete($file);
                }
            }
        } catch (\Exception) {
            // Ignore if snapshot doesn't exist
        }
    }

    protected function snapshotExists(string $name): bool
    {
        $repository = app(\Weslinkde\PostgresTools\PostgresSnapshotRepository::class);

        return $repository->findByName($name) !== null;
    }

    public function getEnvironmentSetUp($app): void
    {
        $app['config']->set('database.default', 'pgsql');
        $app['config']->set('database.connections.pgsql', [
            'driver' => 'pgsql',
            'host' => env('DB_HOST', '127.0.0.1'),
            'port' => env('DB_PORT', '5433'),
            'database' => env('DB_DATABASE', 'postgres_tools_test'),
            'username' => env('DB_USERNAME', 'postgres_tools_test'),
            'password' => env('DB_PASSWORD', 'secret'),
        ]);

        $app['config']->set('postgres-tools.default_connection', 'pgsql');
        $app['config']->set('postgres-tools.disk', 'snapshots');
        $app['config']->set('postgres-tools.temporary_directory_path', storage_path('framework/testing/temp'));

        $app['config']->set('filesystems.disks.snapshots', [
            'driver' => 'local',
            'root' => storage_path('framework/testing/snapshots'),
        ]);

        // Ensure directories exist
        if (! is_dir(storage_path('framework/testing/snapshots'))) {
            mkdir(storage_path('framework/testing/snapshots'), 0755, true);
        }
        if (! is_dir(storage_path('framework/testing/temp'))) {
            mkdir(storage_path('framework/testing/temp'), 0755, true);
        }
    }
}
