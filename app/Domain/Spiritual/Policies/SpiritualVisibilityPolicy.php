<?php

namespace App\Domain\Spiritual\Policies;

use App\Domain\Enums\CanonicalCensure;
use App\Domain\Enums\CapitalVice;
use App\Domain\Enums\DemonicInfluenceStage;
use App\Domain\Enums\SpiritualObserverContext;
use App\Domain\Enums\SpiritualVisibilityClass;
use App\Domain\Enums\TheologicalVirtue;
use App\Models\CharacterCanonicalState;
use App\Models\CharacterSpiritualState;
use App\Models\DemonicInfluence;
use App\Models\Scandal;

final class SpiritualVisibilityPolicy
{
    public function classForFacet(string $facet): string
    {
        if (in_array($facet, ['excommunication', 'interdict', 'baptized', 'orders_grade', 'matrimonial_bond'], true)) {
            return SpiritualVisibilityClass::PUBLIC;
        }

        if (in_array($facet, ['eucharist_standing', 'confirmed', 'last_anointing'], true)) {
            return SpiritualVisibilityClass::CLERGY;
        }

        if ($facet === 'confessed_matter') {
            return SpiritualVisibilityClass::SEALED;
        }

        if (in_array($facet, array_merge(TheologicalVirtue::all(), CapitalVice::all(), ['despair', 'repentance', 'grave_unconfessed', 'temptation', 'personal_corruption']), true)) {
            return SpiritualVisibilityClass::PRIVATE;
        }

        if (in_array($facet, ['demonic_influence', 'possession_signs'], true)) {
            return SpiritualVisibilityClass::INFERABLE;
        }

        return SpiritualVisibilityClass::EVENT;
    }

    public function inferableBand(int $value, int $threshold): ?string
    {
        if ($value >= 90) {
            return 'overwhelming';
        }
        if ($value >= $threshold) {
            return 'marked';
        }

        return null;
    }

    /**
     * @param Scandal[] $scandals
     */
    public function publicCanonical(CharacterCanonicalState $canonical, array $scandals = []): array
    {
        $public = [
            'baptized' => (bool) $canonical->is_baptized,
            'orders_grade' => $canonical->holy_orders_grade,
            'matrimonial_bond_character_id' => $canonical->matrimonial_bond_character_id,
            'censure' => $canonical->censure,
        ];

        if (CanonicalCensure::isPublic($canonical->censure)) {
            $public['excommunication'] = $canonical->censure === CanonicalCensure::EXCOMMUNICATION;
            $public['interdict'] = $canonical->censure === CanonicalCensure::INTERDICT_EFFECT;
        }

        $public['scandals'] = [];
        foreach ($scandals as $scandal) {
            if ($scandal->is_current) {
                $public['scandals'][] = [
                    'facet' => $scandal->facet,
                    'severity_band' => $scandal->severity >= 75 ? 'grave' : 'known',
                    'publicity' => $scandal->publicity,
                ];
            }
        }

        return $public;
    }

    public function clergyCanonical(CharacterCanonicalState $canonical): array
    {
        return [
            'eucharist_standing' => $canonical->eucharist_standing,
            'confirmed' => (bool) $canonical->is_confirmed,
            'last_anointing_date' => optional($canonical->last_anointing_date)->toDateString(),
            'orders_grade' => $canonical->holy_orders_grade,
            'censure' => $canonical->censure,
            'censure_source_type' => $canonical->censure_source_type,
            'censure_source_id' => $canonical->censure_source_id,
        ];
    }

    public function privateInterior(CharacterSpiritualState $state, CharacterCanonicalState $canonical): array
    {
        $payload = [
            TheologicalVirtue::FAITH => (int) $state->faith,
            TheologicalVirtue::HOPE => (int) $state->hope,
            TheologicalVirtue::CHARITY => (int) $state->charity,
            CapitalVice::PRIDE => (int) $state->pride,
            CapitalVice::GREED => (int) $state->greed,
            CapitalVice::LUST => (int) $state->lust,
            CapitalVice::ENVY => (int) $state->envy,
            CapitalVice::GLUTTONY => (int) $state->gluttony,
            CapitalVice::WRATH => (int) $state->wrath,
            CapitalVice::SLOTH => (int) $state->sloth,
            'despair' => (int) $state->despair,
            'repentance' => $state->repentance,
            'personal_corruption' => (int) $state->personal_corruption,
            'grave_unconfessed' => (bool) $canonical->grave_unconfessed,
        ];

        return $payload;
    }

    public function inferableFrom(CharacterSpiritualState $state, ?DemonicInfluence $influence): array
    {
        $threshold = (int) config('spiritual.inferable_threshold', 70);
        $bands = [];

        foreach (array_merge(CapitalVice::all(), ['despair']) as $facet) {
            $band = $this->inferableBand((int) $state->{$facet}, $threshold);
            if ($band !== null) {
                $bands[$facet] = $band;
            }
        }

        if ($influence && DemonicInfluenceStage::publiclyInferable($influence->stage)) {
            $bands['possession_signs'] = $influence->stage;
        }

        return $bands;
    }

    public function allowsExactScores(string $context): bool
    {
        return in_array($context, [SpiritualObserverContext::SELF, SpiritualObserverContext::ADMIN], true);
    }
}
