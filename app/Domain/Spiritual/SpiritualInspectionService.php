<?php

namespace App\Domain\Spiritual;

use App\Domain\Enums\CorruptionSubjectType;
use App\Domain\Enums\SpiritualObserverContext;
use App\Models\Character;
use App\Models\Penance;
use App\Models\SacramentRecord;
use App\Models\SpiritualActRecord;
use App\Models\SpiritualDispositionLedger;
use App\Models\SpiritualKnowledge;

final class SpiritualInspectionService
{
    public function __construct(
        private SpiritualStateService $states,
        private SpiritualVisibilityService $visibility,
        private CorruptionService $corruption,
        private DemonicInfluenceService $demonic,
        private TemptationService $temptations,
        private ScandalService $scandals
    ) {
    }

    public function inspectCharacter(Character $character): array
    {
        [$spiritual, $canonical] = $this->states->ensure($character->world, $character);
        $view = $this->visibility->viewFor(null, $character, SpiritualObserverContext::ADMIN);

        return [
            'character_id' => $character->id,
            'world_id' => $character->world_id,
            'view' => $view->toArray(),
            'spiritual_state' => $spiritual->toArray(),
            'canonical_state' => $canonical->toArray(),
            'demonic_resistance' => $this->states->demonicResistance($spiritual, $canonical),
            'ledger' => SpiritualDispositionLedger::query()
                ->where('character_id', $character->id)
                ->orderByDesc('id')
                ->limit(50)
                ->get()
                ->toArray(),
            'acts' => SpiritualActRecord::query()
                ->where('character_id', $character->id)
                ->orderByDesc('id')
                ->limit(50)
                ->get()
                ->toArray(),
            'sacraments' => SacramentRecord::query()
                ->where('subject_character_id', $character->id)
                ->orderByDesc('id')
                ->get()
                ->toArray(),
            'penances' => Penance::query()->where('character_id', $character->id)->orderByDesc('id')->get()->toArray(),
            'scandals' => $this->scandals->currentFor($character)->toArray(),
            'temptations' => $this->temptations->currentFor($character)->toArray(),
            'demonic_influence' => optional($this->demonic->current($character))->toArray(),
            'character_corruption' => optional($this->corruption->current(CorruptionSubjectType::CHARACTER, (int) $character->id))->toArray(),
            'knowledge_about' => SpiritualKnowledge::query()
                ->where('subject_character_id', $character->id)
                ->orderByDesc('id')
                ->get()
                ->toArray(),
        ];
    }

    public function inspectSubject(string $subjectType, int $subjectId): array
    {
        $state = $this->corruption->current($subjectType, $subjectId);

        return [
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'corruption' => optional($state)->toArray(),
            'modifiers' => $this->corruption->modifiers($subjectType, $subjectId),
            'spread_hints' => $this->corruption->spreadHints($subjectType, $subjectId),
            'history' => $state
                ? $state->events()->orderByDesc('id')->limit(50)->get()->toArray()
                : [],
            'sibling_subjects_note' => 'Corruption rows are per subject type. An army row never replaces a territory row.',
        ];
    }
}
