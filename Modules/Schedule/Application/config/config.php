<?php

return [
    'name' => 'Schedule',
    'change_request' => [
        // Test default: show past slots so workflow can be verified end-to-end.
        // Flip to false later to hide already-happened slots from the picker.
        'show_past_slots' => (bool) env('SCHEDULE_CHANGE_REQUEST_SHOW_PAST_SLOTS', true),
    ],
    'initialize_monthly_schedule' => [
        // Production default: restrict initialization to the current or next month.
        // The legacy environment variable name is retained for compatibility.
        'strict_next_month_only' => (bool) env('SCHEDULE_STRICT_NEXT_MONTH_ONLY', true),

        // Test mode option: when strict mode is off, allow from current month.
        'allow_current_month_in_test' => (bool) env('SCHEDULE_ALLOW_CURRENT_MONTH_FOR_TEST', false),
    ],
];
