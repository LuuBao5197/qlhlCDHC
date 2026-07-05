<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\TestingDatabaseGuard;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        TestingDatabaseGuard::assertSafeBeforeBoot();
        parent::setUp();

        $this->applyTestingDatabaseConfig();
        TestingDatabaseGuard::assertSafeAfterBoot();
    }

    protected function beforeRefreshingDatabase()
    {
        TestingDatabaseGuard::assertSafeBeforeBoot();
        $this->applyTestingDatabaseConfig();
        TestingDatabaseGuard::assertSafeAfterBoot();
    }

    protected function applyTestingDatabaseConfig(): void
    {
        config([
            'app.env' => 'testing',
            'database.default' => env('DB_CONNECTION', config('database.default')),
        ]);

        $connection = config('database.default');

        config([
            "database.connections.{$connection}.driver" => env('DB_CONNECTION', $connection),
            "database.connections.{$connection}.host" => env('DB_HOST', config("database.connections.{$connection}.host")),
            "database.connections.{$connection}.port" => env('DB_PORT', config("database.connections.{$connection}.port")),
            "database.connections.{$connection}.database" => env('DB_DATABASE', config("database.connections.{$connection}.database")),
            "database.connections.{$connection}.username" => env('DB_USERNAME', config("database.connections.{$connection}.username")),
            "database.connections.{$connection}.password" => env('DB_PASSWORD', config("database.connections.{$connection}.password")),
            "database.connections.{$connection}.unix_socket" => env('DB_SOCKET', config("database.connections.{$connection}.unix_socket")),
        ]);
    }

}
