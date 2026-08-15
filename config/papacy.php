<?php

return [
    'majority_numerator' => 2,
    'majority_denominator' => 3,
    'deadlock_after_rounds' => 3,
    'max_voting_rounds' => 8,
    'min_quorum' => 3,
    'min_quorum_extraordinary' => 1,
    'assembly_delay_ticks' => 1,

    'weights' => [
        'theology' => 30,
        'theology_oppose' => 25,
        'faction' => 35,
        'relationship' => 1,
        'ambition' => 40,
        'reputation' => 20,
        'compromise' => 50,
        'deadlock_fatigue' => 20,
        'realm_tie' => 20,
    ],

    'legitimacy' => [
        'base' => 8000,
        'vacancy_decay_per_tick' => 80,
        'schism_split' => 3500,
        'enthronement_restore' => 500,
        'contested_penalty' => 1200,
        'reconciliation_restore' => 2000,
    ],

    'extraordinary' => [
        'relocate_if_rome_inaccessible' => true,
        'expand_to_bishops_if_below_quorum' => true,
        'imperial_appointment' => [
            'enabled' => true,
            'requires_college_extinct' => true,
            'requires_rome_inaccessible' => true,
            'min_apocalypse_stage' => 'collapse_of_offices',
        ],
    ],

    'secular_cannot_select_pope' => true,
];
