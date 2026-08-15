<?php

use App\Domain\Enums\CapitalVice;
use App\Domain\Enums\CorruptionKind;
use App\Domain\Enums\SacramentType;
use App\Domain\Enums\SpiritualActType;
use App\Domain\Enums\TheologicalVirtue;

return [
    'starting' => [
        TheologicalVirtue::FAITH => 40,
        TheologicalVirtue::HOPE => 40,
        TheologicalVirtue::CHARITY => 40,
        CapitalVice::PRIDE => 20,
        CapitalVice::GREED => 20,
        CapitalVice::LUST => 20,
        CapitalVice::ENVY => 20,
        CapitalVice::GLUTTONY => 20,
        CapitalVice::WRATH => 20,
        CapitalVice::SLOTH => 20,
        'despair' => 0,
        'repentance' => 'none',
    ],

    'inferable_threshold' => 70,
    'grave_vice_threshold' => 80,

    'demonic_resistance' => [
        'faith_weight' => 0.4,
        'confirmation_bonus' => 15,
        'anointing_bonus' => 8,
    ],

    'act_effects' => [
        SpiritualActType::MURDER => [
            'deltas' => [CapitalVice::WRATH => 18, TheologicalVirtue::CHARITY => -16, TheologicalVirtue::HOPE => -6, 'despair' => 8],
            'grave_unconfessed' => true,
            'scandal_if_public' => ['facet' => 'murder', 'severity' => 80],
            'corruption' => ['kind' => CorruptionKind::BLOODGUILT, 'intensity' => 12],
            'repentance' => 'none',
        ],
        SpiritualActType::BETRAYAL => [
            'deltas' => [CapitalVice::PRIDE => 8, CapitalVice::GREED => 6, TheologicalVirtue::FAITH => -8, TheologicalVirtue::CHARITY => -10],
            'grave_unconfessed' => true,
            'scandal_if_public' => ['facet' => 'betrayal', 'severity' => 55],
        ],
        SpiritualActType::MERCY => [
            'deltas' => [TheologicalVirtue::CHARITY => 12, TheologicalVirtue::HOPE => 6, CapitalVice::WRATH => -8, 'despair' => -6],
            'repentance' => 'stirring',
        ],
        SpiritualActType::ALMSGIVING => [
            'deltas' => [TheologicalVirtue::CHARITY => 10, CapitalVice::GREED => -10, CapitalVice::PRIDE => -4],
        ],
        SpiritualActType::ADULTERY => [
            'deltas' => [CapitalVice::LUST => 16, TheologicalVirtue::FAITH => -8, TheologicalVirtue::CHARITY => -10],
            'grave_unconfessed' => true,
            'scandal_if_public' => ['facet' => 'adultery', 'severity' => 70],
        ],
        SpiritualActType::SACRILEGE => [
            'deltas' => [TheologicalVirtue::FAITH => -20, CapitalVice::PRIDE => 12, 'despair' => 6],
            'grave_unconfessed' => true,
            'corruption' => ['kind' => CorruptionKind::DESECRATION, 'intensity' => 20],
            'scandal_if_public' => ['facet' => 'sacrilege', 'severity' => 90],
        ],
        SpiritualActType::OATH_BREAKING => [
            'deltas' => [TheologicalVirtue::FAITH => -10, CapitalVice::PRIDE => 8, TheologicalVirtue::CHARITY => -6],
            'grave_unconfessed' => true,
            'scandal_if_public' => ['facet' => 'oath_breaking', 'severity' => 50],
        ],
        SpiritualActType::CHARITY => [
            'deltas' => [TheologicalVirtue::CHARITY => 14, CapitalVice::ENVY => -6, CapitalVice::GREED => -6],
        ],
        SpiritualActType::FASTING => [
            'deltas' => [CapitalVice::GLUTTONY => -12, CapitalVice::LUST => -4, TheologicalVirtue::FAITH => 6, CapitalVice::PRIDE => 2],
        ],
        SpiritualActType::PILGRIMAGE => [
            'deltas' => [TheologicalVirtue::FAITH => 10, TheologicalVirtue::HOPE => 8, 'despair' => -8, CapitalVice::SLOTH => -6],
            'repentance' => 'stirring',
        ],
        SpiritualActType::CONFESSION => [
            'deltas' => [TheologicalVirtue::FAITH => 6, TheologicalVirtue::HOPE => 8, 'despair' => -10],
            'grave_unconfessed' => false,
            'repentance' => 'contrite',
        ],
        SpiritualActType::DESECRATION => [
            'deltas' => [TheologicalVirtue::FAITH => -18, CapitalVice::WRATH => 8, CapitalVice::PRIDE => 10, 'despair' => 10],
            'grave_unconfessed' => true,
            'corruption' => ['kind' => CorruptionKind::DESECRATION, 'intensity' => 25],
            'scandal_if_public' => ['facet' => 'desecration', 'severity' => 85],
        ],
        SpiritualActType::COWARDICE => [
            'deltas' => [CapitalVice::SLOTH => 10, TheologicalVirtue::HOPE => -10, 'despair' => 12, TheologicalVirtue::CHARITY => -4],
        ],
        SpiritualActType::MARTYRDOM => [
            'deltas' => [TheologicalVirtue::FAITH => 25, TheologicalVirtue::HOPE => 20, TheologicalVirtue::CHARITY => 18, 'despair' => -20, CapitalVice::PRIDE => -8],
            'grave_unconfessed' => false,
            'cleanses_character_corruption' => 30,
            'repentance' => 'contrite',
        ],
    ],

    'sacrament_deltas' => [
        SacramentType::BAPTISM => [TheologicalVirtue::FAITH => 8, TheologicalVirtue::HOPE => 8, TheologicalVirtue::CHARITY => 8, 'despair' => -8],
        SacramentType::EUCHARIST => [TheologicalVirtue::FAITH => 4, TheologicalVirtue::HOPE => 4, TheologicalVirtue::CHARITY => 6],
        SacramentType::CONFIRMATION => [TheologicalVirtue::FAITH => 10],
        SacramentType::ANOINTING => [TheologicalVirtue::HOPE => 12, 'despair' => -16],
        SacramentType::CONFESSION => [TheologicalVirtue::HOPE => 8, 'despair' => -10],
    ],
];
