<?php

namespace Tests;

use LogicException;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function beforeRefreshingDatabase(): void
    {
        $connection = config('database.default');
        $database = config("database.connections.{$connection}.database");

        if (
            app()->environment() !== 'testing'
            || $connection !== 'sqlite'
            || $database !== ':memory:'
        ) {
            throw new LogicException(
                "BLOCKED: Test database unsafe. "."env=".app()->environment(). ", connection={$connection}, database={$database}"
            );
        }
    }
}
