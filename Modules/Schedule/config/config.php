<?php

return [
    'name' => 'Schedule',
    'initialize_monthly_schedule' => [
        // Production default: only allow initializing exactly next month.
        'strict_next_month_only' => (bool) env('SCHEDULE_STRICT_NEXT_MONTH_ONLY', true),

        // Test mode option: when strict mode is off, allow from current month.
        'allow_current_month_in_test' => (bool) env('SCHEDULE_ALLOW_CURRENT_MONTH_FOR_TEST', false),
    ],
];
