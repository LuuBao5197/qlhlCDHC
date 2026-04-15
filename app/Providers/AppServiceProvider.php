<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Factory::guessFactoryNamesUsing(static function (string $modelName): string {
            if (Str::startsWith($modelName, 'Modules\\') && Str::contains($modelName, '\\Models\\')) {
                return Str::replace('\\Models\\', '\\Database\\Factories\\', $modelName).'Factory';
            }

            return 'Database\\Factories\\'.class_basename($modelName).'Factory';
        });
    }
}
