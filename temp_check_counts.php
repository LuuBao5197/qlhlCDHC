<?php
require __DIR__ . '/vendor/autoload.php';
$app = require __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
use Modules\Schedule\Models\Plans;
use Modules\Schedule\Models\MonthlySchedule;
use Modules\Schedule\Models\ScheduleSlot;
echo 'plans: ' . Plans::count() . PHP_EOL;
echo 'monthly: ' . MonthlySchedule::count() . PHP_EOL;
echo 'slots: ' . ScheduleSlot::count() . PHP_EOL;
