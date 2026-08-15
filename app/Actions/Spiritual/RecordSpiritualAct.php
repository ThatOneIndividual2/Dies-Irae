<?php

namespace App\Actions\Spiritual;

use App\Domain\Enums\CorruptionSubjectType;
use App\Domain\Spiritual\CanonicalStandingService;
use App\Domain\Spiritual\Catalogs\SpiritualActCatalog;
use App\Domain\Spiritual\CorruptionService;
use App\Domain\Spiritual\ScandalService;
use App\Domain\Spiritual\SpiritualStateService;
use App\Domain\Support\AfterCommit;
use App\Domain\Support\Transactional;
use App\Domain\Support\WorldBoundary;
use App\Events\Spiritual\ScandalBroke;
use App\Events\Spiritual\SpiritualActRecorded;
use App\Models\Character;
use App\Models\SpiritualActRecord;
use App\Models\World;
use Carbon\CarbonInterface;

final class RecordSpiritualAct
{
    public function __construct(
        private SpiritualActCatalog $catalog,
        private SpiritualStateService $states,
        private CanonicalStandingService $canonical,
        private ScandalService $scandals,
        private CorruptionService $corruption
    ) {
    }

    public function execute(
        World $world,
        Character $character,
        string $actType,
        CarbonInterface $date,
        bool $isPublic = false,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?array $metadata = null
    ): SpiritualActRecord {
        WorldBoundary::assertSameWorld((int) $world->id, (int) $character->world_id, 'spiritual act');

        return Transactional::run(function () use ($world, $character, $actType, $date, $isPublic, $sourceType, $sourceId, $metadata) {
            $this->states->ensure($world, $character);
            $definition = $this->catalog->definition($actType);
            $deltas = $definition['deltas'] ?? [];
            $repentance = $definition['repentance'] ?? null;

            $apply = $deltas;
            if (is_string($repentance)) {
                $apply['repentance'] = $repentance;
            }

            $this->states->applyDeltas(
                $world,
                $character,
                $apply,
                'act:'.$actType,
                $date,
                $sourceType,
                $sourceId,
                $metadata
            );

            if (array_key_exists('grave_unconfessed', $definition)) {
                $this->canonical->setGraveUnconfessed($world, $character, (bool) $definition['grave_unconfessed']);
            }

            $record = SpiritualActRecord::query()->create([
                'world_id' => $world->id,
                'character_id' => $character->id,
                'act_type' => $actType,
                'occurred_date' => $date->toDateString(),
                'is_public' => $isPublic,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'deltas' => $deltas,
                'metadata' => $metadata,
            ]);

            $scandal = null;
            if ($isPublic && isset($definition['scandal_if_public'])) {
                $spec = $definition['scandal_if_public'];
                $scandal = $this->scandals->record(
                    $world,
                    $character,
                    $spec['facet'],
                    (int) $spec['severity'],
                    $date,
                    $metadata['publicity'] ?? 'settlement',
                    SpiritualActRecord::class,
                    $record->id
                );
            }

            if (isset($definition['corruption'])) {
                $this->corruption->apply(
                    $world,
                    CorruptionSubjectType::CHARACTER,
                    (int) $character->id,
                    $definition['corruption']['kind'],
                    (int) $definition['corruption']['intensity'],
                    $date,
                    SpiritualActRecord::class,
                    $record->id
                );
            }

            if (!empty($definition['cleanses_character_corruption'])) {
                $this->corruption->cleanse(
                    $world,
                    CorruptionSubjectType::CHARACTER,
                    (int) $character->id,
                    (int) $definition['cleanses_character_corruption'],
                    $date,
                    SpiritualActRecord::class,
                    $record->id
                );
            }

            AfterCommit::dispatch(function () use ($record, $scandal) {
                event(new SpiritualActRecorded($record->id, $record->character_id, $record->act_type));
                if ($scandal) {
                    event(new ScandalBroke($scandal->id, $scandal->character_id, $scandal->facet));
                }
            });

            return $record;
        });
    }
}
