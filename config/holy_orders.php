<?php

return [
    'default_manpower_cap' => 80,
    'starting_treasury' => 20,
    'starting_reputation' => 40,
    'starting_morale' => 70,
    'starting_consecration' => 40,
    'starting_corruption_resistance' => 50,

    /*
     * Quality starts at 1.0. Bonuses are small and stack against penalties.
     * A healthy order is better than a levy. A corrupt, unrecognized, understrength
     * order is worse. They are never a blanket upgrade.
     */
    'modifiers' => [
        'vow_integrity' => 0.08,
        'morale' => 0.10,
        'recognized_relic' => 0.12,
        'unrecognized_relic' => 0.02,
        'chaplain_presence' => 0.08,
        'consecration' => 0.10,
        'leadership' => 0.08,
        'reputation' => 0.05,
        'corruption_resistance_vs_demon' => 0.08,
        'corruption_penalty' => -0.20,
        'fractured_legitimacy' => -0.15,
        'lost_recognition' => -0.12,
        'schism' => -0.18,
        'excommunication' => -0.25,
        'political_obligation' => -0.10,
        'understrength' => -0.10,
        'floor' => 0.45,
        'ceiling' => 1.45,
    ],

    'controversial_corruption' => 40,
    'fracture_corruption' => 70,
    'knight_manpower_cost' => 3,
    'sergeant_manpower_cost' => 1,
    'chaplain_manpower_cost' => 1,
];
