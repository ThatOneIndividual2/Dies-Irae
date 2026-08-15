<?php

return [
    'kinds' => [
        'doctrinal_heresy' => [
            'spread' => ['clergy', 'preaching', 'noble_patronage', 'migration', 'trade_routes', 'war'],
            'church' => ['theological_condemnation', 'local_synod', 'excommunication', 'inquisitorial_investigation', 'reconciliation', 'penance', 'papal_intervention', 'military_suppression'],
            'secular' => ['tolerate', 'suppress', 'patronize', 'expel', 'imprison_leaders', 'confiscate_property'],
            'starts_visible' => 'rumored',
            'failed_suppression_delta' => 20,
        ],
        'schism' => [
            'spread' => ['clergy', 'noble_patronage', 'war', 'preaching'],
            'church' => ['local_synod', 'papal_intervention', 'excommunication', 'interdict', 'reconciliation'],
            'secular' => ['tolerate', 'patronize', 'negotiate', 'suppress'],
            'starts_visible' => 'public',
            'failed_suppression_delta' => 10,
        ],
        'apostasy' => [
            'spread' => ['charismatic_leaders', 'despair', 'corruption'],
            'church' => ['penance', 'reconciliation', 'excommunication'],
            'secular' => ['tolerate', 'expel', 'imprison_leaders'],
            'starts_visible' => 'rumored',
            'failed_suppression_delta' => 5,
        ],
        'popular_movement' => [
            'spread' => ['preaching', 'migration', 'famine', 'plague', 'despair', 'charismatic_leaders', 'trade_routes'],
            'church' => ['preaching', 'local_synod', 'penance', 'papal_intervention'],
            'secular' => ['tolerate', 'negotiate', 'suppress', 'exploit'],
            'starts_visible' => 'public',
            'failed_suppression_delta' => 25,
        ],
        'clandestine_cult' => [
            'spread' => ['clergy', 'migration', 'corruption', 'charismatic_leaders'],
            'church' => ['inquisitorial_investigation', 'preaching', 'penance'],
            'secular' => ['tolerate', 'suppress', 'imprison_leaders'],
            'starts_visible' => 'secret',
            'failed_suppression_delta' => 15,
        ],
        'demonic_cult' => [
            'spread' => ['corruption', 'despair', 'plague', 'war', 'charismatic_leaders'],
            'church' => ['inquisitorial_investigation', 'excommunication', 'military_suppression', 'papal_intervention'],
            'secular' => ['suppress', 'imprison_leaders', 'confiscate_property'],
            'starts_visible' => 'secret',
            'failed_suppression_delta' => 30,
        ],
        'anti_clerical_unrest' => [
            'spread' => ['famine', 'plague', 'despair', 'war', 'preaching'],
            'church' => ['preaching', 'interdict', 'papal_intervention'],
            'secular' => ['suppress', 'negotiate', 'exploit', 'tolerate'],
            'starts_visible' => 'public',
            'failed_suppression_delta' => 20,
        ],
        'false_prophet_movement' => [
            'spread' => ['charismatic_leaders', 'preaching', 'despair', 'plague', 'famine'],
            'church' => ['local_synod', 'theological_condemnation', 'preaching', 'excommunication'],
            'secular' => ['tolerate', 'patronize', 'expel', 'imprison_leaders'],
            'starts_visible' => 'public',
            'failed_suppression_delta' => 15,
        ],
    ],

    'church_response_ranks' => [
        'preaching' => ['priest', 'deacon', 'bishop', 'archbishop', 'pope', 'abbot'],
        'theological_condemnation' => ['bishop', 'archbishop', 'pope'],
        'local_synod' => ['bishop', 'archbishop', 'pope'],
        'excommunication' => ['pope', 'archbishop', 'bishop'],
        'interdict' => ['pope', 'archbishop', 'bishop'],
        'inquisitorial_investigation' => ['bishop', 'archbishop', 'pope', 'legate'],
        'reconciliation' => ['priest', 'bishop', 'archbishop', 'pope'],
        'penance' => ['priest', 'bishop', 'archbishop', 'pope'],
        'military_suppression' => ['pope', 'archbishop', 'bishop'],
        'papal_intervention' => ['pope'],
    ],

    'detection' => [
        'public_reveal_sources' => ['investigation', 'inquisitorial_investigation', 'clergy_report'],
        'sealed_sources' => ['confession'],
        'false_accusation_does_not_reveal' => true,
        'rumor_does_not_confirm' => true,
    ],

    'spread' => [
        'despair_threshold' => 20,
        'corruption_threshold' => 10,
        'plague_requires_active' => true,
        'famine_food_stores_max' => 0,
        'trade_requires_adjacency' => true,
        'base_intensity' => 8,
    ],

    'suppression' => [
        'success_if_intensity_below' => 15,
        'spawn_unrest_from' => ['popular_movement', 'doctrinal_heresy'],
        'deepen_secrecy_for' => ['clandestine_cult', 'demonic_cult'],
    ],
];
