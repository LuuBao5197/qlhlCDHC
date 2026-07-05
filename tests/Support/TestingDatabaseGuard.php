<?php

namespace Tests\Support;

use Illuminate\Support\Facades\DB;
use RuntimeException;

class TestingDatabaseGuard
{
    public static function assertSafeBeforeBoot(): void
    {
        $appEnv = self::readEnv('APP_ENV');
        $connection = self::readEnv('DB_CONNECTION');
        $database = self::readEnv('DB_DATABASE');

        if ($appEnv !== 'testing' || $connection !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(sprintf(
                'BLOCKED: unsafe pre-boot test database config. APP_ENV=%s, DB_CONNECTION=%s, DB_DATABASE=%s',
                $appEnv,
                $connection,
                $database
            ));
        }

        $configCachePath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'bootstrap' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR . 'config.php';
        if (is_file($configCachePath)) {
            $configCache = (string) file_get_contents($configCachePath);
            $looksSafe = str_contains($configCache, "'driver' => 'sqlite'")
                && str_contains($configCache, ":memory:")
                && ! str_contains($configCache, "'driver' => 'mysql'")
                && ! str_contains($configCache, "'driver' => 'mariadb'");

            if (! $looksSafe) {
                throw new RuntimeException('BLOCKED: bootstrap/cache/config.php does not look like a safe sqlite :memory: testing cache.');
            }
        }
    }

    public static function assertSafeAfterBoot(): void
    {
        if (config('app.env') !== 'testing') {
            throw new RuntimeException('BLOCKED: APP_ENV is not testing after boot.');
        }

        $connection = (string) config('database.default');
        $driver = (string) config("database.connections.{$connection}.driver");
        $database = (string) config("database.connections.{$connection}.database");

        if ($connection !== 'sqlite' || $driver !== 'sqlite' || $database !== ':memory:') {
            throw new RuntimeException(sprintf(
                'BLOCKED: unsafe runtime database config. connection=%s, driver=%s, database=%s',
                $connection,
                $driver,
                $database
            ));
        }

        $pdoDriver = DB::connection()->getDriverName();
        $pdoDatabase = DB::connection()->getDatabaseName();

        if ($pdoDriver !== 'sqlite' || $pdoDatabase !== ':memory:') {
            throw new RuntimeException(sprintf(
                'BLOCKED: unsafe live database connection. driver=%s, database=%s',
                $pdoDriver,
                $pdoDatabase
            ));
        }
    }

    private static function readEnv(string $key): string
    {
        $value = getenv($key);
        if ($value === false || $value === '') {
            $value = $_ENV[$key] ?? $_SERVER[$key] ?? '';
        }

        return is_string($value) ? $value : '';
    }
}
