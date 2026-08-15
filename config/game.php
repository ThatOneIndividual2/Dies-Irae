<?php

return [
    'name' => env('GAME_NAME', 'Dies Irae'),

    'default_world_name' => env('GAME_WORLD_NAME', 'Europa'),
    'default_world_slug' => env('GAME_WORLD_SLUG', 'lys-1348'),

    'content_pack' => env('GAME_CONTENT_PACK', 'europa'),

    'fixture_file' => env('GAME_FIXTURE_FILE', 'fixture.json'),

    'calendar' => [
        'default_start_date' => env('GAME_START_DATE', '1348-06-24'),
        'default_game_speed' => (int) env('GAME_SPEED', 2),
        'seconds_per_day_by_speed' => [
            0 => null,
            1 => 7200,
            2 => 3600,
            3 => 1800,
            4 => 900,
        ],
        'max_days_per_invocation' => 30,
        'max_events_per_batch' => 50,
        'max_event_attempts' => 5,
        'catch_up_day_cap' => 30,
        'event_types' => [
            'territory_plague_tick',
            'territory_famine_tick',
            'harvest_season_tick',
            'famine_tick',
            'population_census',
            'apocalypse_stage_check',
            'territory_despair_tick',
            'hell_incursion_pulse',
            'narrative_event_pulse',
            'campaign_pulse',
            'campaign_beat',
        ],
    ],

    'event_types' => [
        'territory_plague_tick',
        'territory_famine_tick',
        'harvest_season_tick',
        'famine_tick',
        'population_census',
        'apocalypse_stage_check',
        'territory_despair_tick',
        'hell_incursion_pulse',
        'narrative_event_pulse',
        'campaign_pulse',
        'campaign_beat',
    ],

    'succession' => [
        'default_law_key' => 'agnatic_primogeniture',
    ],

    'title_ranks' => [
        'barony',
        'county',
        'duchy',
        'kingdom',
        'empire',
    ],

    'spiritual_office_ranks' => [
        'priest',
        'abbot',
        'bishop',
        'archbishop',
        'cardinal',
        'pope',
    ],

    'isolation' => [
        'database' => env('DB_DATABASE', 'dies_irae_game'),
        'forbidden_databases' => [
            'feudalism_game',
            'feudalism_game_testing',
            'got_game',
            'got_game_testing',
        ],
    ],

    'levy_ratio' => 0.08,
    'ruin_population_threshold' => 80,

    'battle' => [
        'human_k_factor' => 0.35,
        'supernatural_k_factor' => 0.28,
    ],

    'demo_user' => [
        'email' => env('DIES_IRAE_DEMO_EMAIL', 'lord@diesirae.test'),
        'password' => env('DIES_IRAE_DEMO_PASSWORD', 'password'),
    ],
];
