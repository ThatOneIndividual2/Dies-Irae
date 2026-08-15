<?php

return [
    /*
    | Appointment modes are policy, not frozen medieval law.
    | A see-level investiture_rights row overrides the office default.
    */
    'appointment_modes' => [
        'pope' => 'conclave',
        'cardinal' => 'papal',
        'archbishop' => 'papal',
        'bishop' => 'papal',
        'priest' => 'local',
        'deacon' => 'local',
        'abbot' => 'abbatial_election',
        'prior' => 'abbatial_election',
        'legate' => 'papal',
        'grand_master' => 'internal_election',
        'master' => 'internal_election',
    ],

    'powers' => [
        'excommunicate' => ['pope', 'archbishop', 'bishop'],
        'lift_excommunication' => ['pope', 'archbishop', 'bishop'],
        'interdict' => ['pope', 'archbishop', 'bishop'],
        'appoint_clergy' => ['pope', 'archbishop', 'bishop', 'abbot'],
        'remove_clergy' => ['pope', 'archbishop', 'bishop', 'abbot'],
        'recognize_saint' => ['pope'],
        'recognize_relic' => ['pope', 'archbishop', 'bishop'],
        'sanction_holy_order' => ['pope'],
        'suppress_holy_order' => ['pope'],
        'withdraw_holy_order_recognition' => ['pope'],
        'declare_heresy' => ['pope', 'archbishop', 'bishop'],
        'legitimize_exorcist' => ['pope', 'bishop'],
        'authorize_extraordinary' => ['pope', 'bishop'],
    ],

    'universal_ranks' => [
        'pope',
    ],

    'sacramental_authority' => [
        'baptism' => ['deacon', 'priest', 'bishop'],
        'confirmation' => ['bishop'],
        'eucharist' => ['priest', 'bishop'],
        'penance' => ['priest', 'bishop'],
        'unction' => ['priest', 'bishop'],
        'orders' => ['bishop'],
        'matrimony' => ['deacon', 'priest', 'bishop'],
    ],

    'orders_grade_weight' => [
        'none' => 0,
        'tonsure' => 10,
        'porter' => 20,
        'lector' => 30,
        'acolyte' => 40,
        'subdeacon' => 50,
        'deacon' => 60,
        'priest' => 70,
        'bishop' => 80,
    ],

    'secular_succession' => [
        'block_if_major_orders' => true,
        'block_if_regular' => true,
        'block_if_excommunicated' => true,
        'major_orders_from' => 'deacon',
    ],

    'papacy' => [
        'unique_per_world' => true,
        'vacancy_is_first_class' => true,
        'antipope_does_not_replace_institution' => true,
        'election_is_conclave_process' => true,
        'secular_cannot_select_pope' => true,
    ],
];
