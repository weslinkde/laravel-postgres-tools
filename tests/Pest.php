<?php

use Weslinkde\PostgresTools\Tests\Integration\IntegrationTestCase;
use Weslinkde\PostgresTools\Tests\TestCase;

uses(TestCase::class)->in('Unit', 'ArchTest.php', 'ExampleTest.php');
uses(IntegrationTestCase::class)->in('Integration');
