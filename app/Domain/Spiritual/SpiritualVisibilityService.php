<?php

namespace App\Domain\Spiritual;

use App\Domain\Enums\SpiritualKnowledgeCertainty;
use App\Domain\Enums\SpiritualObserverContext;
use App\Domain\Spiritual\DTO\SpiritualView;
use App\Domain\Spiritual\Policies\ClericalInsightPolicy;
use App\Domain\Spiritual\Policies\ConfessionSealPolicy;
use App\Domain\Spiritual\Policies\SpiritualVisibilityPolicy;
use App\Domain\Support\WorldBoundary;
use App\Models\Character;
use App\Models\SacramentRecord;
use App\Models\SpiritualKnowledge;
use App\Models\Temptation;
use App\Models\World;
use Carbon\CarbonInterface;
use DomainException;

final class SpiritualVisibilityService
{
    public function __construct(
        private SpiritualStateService $states,
        private SpiritualVisibilityPolicy $visibility,
        private ClericalInsightPolicy $insight,
        private ConfessionSealPolicy $seal,
        private ScandalService $scandals,
        private DemonicInfluenceService $demonic
    ) {
    }

    public function viewFor(?Character $observer, Character $subject, string $context): SpiritualView
    {
        $world = $subject->world;
        [$spiritual, $canonical] = $this->states->ensure($world, $subject);
        $scandals = $this->scandals->currentFor($subject)->all();
        $influence = $this->demonic->current($subject);

        $public = $this->visibility->publicCanonical($canonical, $scandals);
        $inferable = $this->visibility->inferableFrom($spiritual, $influence);
        $clergy = [];
        $private = [];
        $sealed = [];
        $hidden = ['interior_scores', 'temptation', 'unconfessed_grave_matter'];

        $isSelf = $observer && (int) $observer->id === (int) $subject->id;
        $isAdmin = $context === SpiritualObserverContext::ADMIN;

        if ($isSelf) {
            $context = SpiritualObserverContext::SELF;
        }

        if ($observer && $this->insight->maySeeCanonicalStanding($observer, $subject) && in_array($context, [
            SpiritualObserverContext::CLERGY,
            SpiritualObserverContext::CONFESSOR,
            SpiritualObserverContext::EXORCIST,
            SpiritualObserverContext::ADMIN,
        ], true)) {
            $clergy = $this->visibility->clergyCanonical($canonical);
            $hidden = array_values(array_diff($hidden, ['canonical_detail']));
        }

        if ($observer && $this->insight->maySenseDemonicInfluence($observer, $influence)) {
            $clergy['demonic_stage'] = $influence->stage;
            $clergy['demonic_intensity_band'] = $influence->intensity >= 70 ? 'grave' : 'present';
        }

        if ($context === SpiritualObserverContext::SELF || $isAdmin) {
            $private = $this->visibility->privateInterior($spiritual, $canonical);
            $private['temptations'] = Temptation::query()
                ->where('character_id', $subject->id)
                ->where('is_current', true)
                ->get(['vice', 'intensity', 'stage'])
                ->toArray();
            $private['demonic'] = $influence ? [
                'stage' => $influence->stage,
                'intensity' => $influence->intensity,
                'source_type' => $influence->source_type,
                'source_id' => $influence->source_id,
            ] : null;
            $hidden = [];
        }

        if ($observer && in_array($context, [SpiritualObserverContext::CONFESSOR, SpiritualObserverContext::ADMIN], true)) {
            $sealed = SpiritualKnowledge::query()
                ->where('observer_character_id', $observer->id)
                ->where('subject_character_id', $subject->id)
                ->where('is_sealed', true)
                ->get(['facet', 'certainty', 'acquired_date', 'metadata'])
                ->toArray();
        }

        if ($context === SpiritualObserverContext::POLITICAL && $observer) {
            $sealedRows = SpiritualKnowledge::query()
                ->where('observer_character_id', $observer->id)
                ->where('subject_character_id', $subject->id)
                ->where('is_sealed', true)
                ->get();
            foreach ($sealedRows as $row) {
                $this->seal->assertNotPoliticalUse($context, $row->toArray());
            }
        }

        return new SpiritualView(
            characterId: (int) $subject->id,
            context: $context,
            public: $public,
            inferable: $inferable,
            clergy: $clergy,
            private: $private,
            sealed: $sealed,
            hiddenFacets: $hidden,
            isAdmin: $isAdmin
        );
    }

    public function recordSealedConfession(
        World $world,
        Character $confessor,
        Character $penitent,
        SacramentRecord $record,
        CarbonInterface $date
    ): SpiritualKnowledge {
        WorldBoundary::assertSameWorld((int) $world->id, (int) $confessor->world_id, 'confession confessor');
        WorldBoundary::assertSameWorld((int) $world->id, (int) $penitent->world_id, 'confession penitent');

        return SpiritualKnowledge::query()->create([
            'world_id' => $world->id,
            'observer_character_id' => $confessor->id,
            'subject_character_id' => $penitent->id,
            'facet' => 'confessed_matter',
            'certainty' => SpiritualKnowledgeCertainty::CONFESSED,
            'is_sealed' => true,
            'source_type' => SacramentRecord::class,
            'source_id' => $record->id,
            'acquired_date' => $date->toDateString(),
            'metadata' => ['sacrament' => 'confession'],
        ]);
    }

    public function reveal(
        World $world,
        Character $observer,
        Character $subject,
        string $facet,
        string $certainty,
        CarbonInterface $date,
        bool $sealed = false,
        ?string $sourceType = null,
        ?int $sourceId = null
    ): SpiritualKnowledge {
        if ($sealed && $certainty !== SpiritualKnowledgeCertainty::CONFESSED) {
            throw new DomainException('Only confessed matter may be sealed.');
        }

        return SpiritualKnowledge::query()->create([
            'world_id' => $world->id,
            'observer_character_id' => $observer->id,
            'subject_character_id' => $subject->id,
            'facet' => $facet,
            'certainty' => $certainty,
            'is_sealed' => $sealed,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'acquired_date' => $date->toDateString(),
        ]);
    }
}
