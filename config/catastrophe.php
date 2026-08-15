<?php

return [
    'event_types' => [
        'territory_plague_tick',
        'territory_famine_tick',
        'population_census',
    ],

    'viability_floor' => 40,

    'ruin' => [
        'strained_peak_bp' => 7000,
        'depopulated_peak_bp' => 3500,
        'abandoned_peak_bp' => 1000,
        'abandoned_to_ruined_ticks' => 8,
        'disease_strain_bp' => 1500,
        'corruption_enter' => 40,
        'despair_corruption' => 70,
    ],

    'refugee' => [
        'auto_flee_pressure' => 55,
        'road_loss_bp' => 400,
    ],

    'strains' => [
        'black_death',
        'pneumonic_death',
        'infernal_miasma',
    ],
];
