<?php

use App\Domain\Enums\MiracleCategory;
use App\Domain\Enums\SaintEvidenceType;

return [
    'canonization' => [
        'min_evidence_weight' => 40,
        'min_distinct_types' => 2,
        'min_cult_intensity' => 20,
        'evidence_weights' => [
            SaintEvidenceType::MARTYRDOM => 30,
            SaintEvidenceType::INCORRUPT_BODY => 20,
            SaintEvidenceType::MIRACLE_CLAIM => 15,
            SaintEvidenceType::RELIC => 10,
            SaintEvidenceType::REPUTATION => 10,
            SaintEvidenceType::LOCAL_CULT => 10,
            SaintEvidenceType::VITA => 8,
        ],
    ],

    'miracle' => [
        'max_claims_per_place_per_year' => 3,
        'required_source' => true,
    ],

    'pilgrimage' => [
        'faith_act' => true,
        'base_income' => 12,
        'base_prestige' => 8,
        'cult_growth' => 8,
        'plague_link_intensity' => 7000,
    ],

    'relic' => [
        'recognized_pilgrimage_bonus' => 20,
        'forged_pilgrimage_penalty' => 10,
    ],
];
