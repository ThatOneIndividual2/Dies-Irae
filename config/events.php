<?php

return [
    'data_path' => env('EVENT_DATA_PATH', 'database/data/events'),

    'pulse_interval_days' => (int) env('EVENT_PULSE_DAYS', 1),

    'max_new_events_per_pulse' => (int) env('EVENT_PULSE_MAX', 2),

    'max_pending_per_world' => (int) env('EVENT_MAX_PENDING', 6),
];
