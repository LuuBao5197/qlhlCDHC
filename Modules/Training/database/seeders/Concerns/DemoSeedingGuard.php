<?php

namespace Modules\Training\Database\Seeders\Concerns;

use Illuminate\Support\Facades\DB;

trait DemoSeedingGuard
{
    protected function shouldRunDemoSeeding(): bool
    {
        if (! app()->environment('local')) {
            return false;
        }

        if (env('DEMO_SEEDING_CONFIRMATION') !== 'REBUILD_REFERENCE_DATA') {
            return false;
        }

        $targetDatabase = (string) env('DEMO_SEED_TARGET_DB', '');
        if ($targetDatabase === '') {
            return false;
        }

        try {
            $currentDatabase = (string) DB::connection()->getDatabaseName();
        } catch (\Throwable $throwable) {
            return false;
        }

        return $currentDatabase !== '' && $currentDatabase === $targetDatabase;
    }
}
