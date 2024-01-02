<?php

namespace Weslinkde\PostgresTools\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Weslinkde\PostgresTools\PostgresToolsServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getPackageProviders($app)
    {
        return [
            PostgresToolsServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');
    }
}
