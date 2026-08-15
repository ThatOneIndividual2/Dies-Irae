<?php

return [
    'data_path' => env('APOCALYPSE_DATA_PATH', 'database/data/apocalypse'),

    'tick_interval_days' => (int) env('APOCALYPSE_TICK_DAYS', 7),

    'meter_min' => 0,
    'meter_max' => 100,

    /*
    | Composite pressure weights. Church cohesion is inverted (health, not wound).
    */
    'pressure_weights' => [
        'global_corruption' => 0.18,
        'plague_severity' => 0.16,
        'demonic_manifestation' => 0.16,
        'institutional_collapse' => 0.12,
        'famine_pressure' => 0.10,
        'despair' => 0.12,
        'political_fragmentation' => 0.10,
        'church_cohesion_deficit' => 0.06,
    ],

    'healthy_meters' => [
        'church_cohesion',
    ],
];
