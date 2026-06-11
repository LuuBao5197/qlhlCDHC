<?php

return [
    'name' => 'Schedule',
    'holiday_reschedule' => [
        'enabled' => (bool) env('SCHEDULE_HOLIDAY_RESCHEDULE_ENABLED', true),
        'max_shift_days' => (int) env('SCHEDULE_HOLIDAY_RESCHEDULE_MAX_SHIFT_DAYS', 7),
        'allow_weekend_if_template_allows' => (bool) env('SCHEDULE_HOLIDAY_RESCHEDULE_ALLOW_WEEKEND_IF_TEMPLATE_ALLOWS', true),
    ],
    'initialize_monthly_schedule' => [
        // Production default: only allow initializing exactly next month.
        'strict_next_month_only' => (bool) env('SCHEDULE_STRICT_NEXT_MONTH_ONLY', true),

        // Test mode option: when strict mode is off, allow from current month.
        'allow_current_month_in_test' => (bool) env('SCHEDULE_ALLOW_CURRENT_MONTH_FOR_TEST', false),
    ],
];
