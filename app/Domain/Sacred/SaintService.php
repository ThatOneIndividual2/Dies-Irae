<?php

namespace App\Domain\Sacred;

use App\Domain\Enums\SaintEvidenceType;
use App\Domain\Enums\SaintOrigin;
use App\Domain\Enums\SaintRecognitionStatus;
use App\Domain\Sacred\Policies\CanonizationPolicy;
use App\Domain\Sacred\Ports\SacredAuthorityPort;
use App\Domain\Support\WorldBoundary;
use App\Events\SaintRecognized;
use App\Models\Character;
use App\Models\Saint;
use App\Models\SaintCult;
use App\Models\SaintEvidence;
use App\Models\SaintFeast;
use App\Models\SaintPatronage;
use App\Models\SaintShrine;
use App\Models\World;
use Carbon\CarbonInterface;
use DomainException;

final class SaintService
{
    public function __construct(
        private CanonizationPolicy $policy,
        private SacredAuthorityPort $authority
    ) {
    }

    public function registerHistorical(
        World $world,
        string $key,
        string $name,
        CarbonInterface $date,
        ?string $feastDay = null,
        array $patronages = [],
        ?int $shrineTerritoryId = null
    ): Saint {
        $saint = Saint::query()->create([
            'world_id' => $world->id,
            'character_id' => null,
            'key' => $key,
            'name' => $name,
            'origin' => SaintOrigin::HISTORICAL,
            'recognition_status' => SaintRecognitionStatus::CULTUS,
            'feast_day' => $feastDay,
            'reputation' => 20,
            'shrine_territory_id' => $shrineTerritoryId,
            'recognized_date' => null,
        ]);

        if ($feastDay) {
            $this->addFeast($world, $saint, $feastDay, 'feast', $shrineTerritoryId);
        }
        foreach ($patronages as $patronage) {
            $this->addPatronage($world, $saint, $patronage);
        }
        if ($shrineTerritoryId) {
            $this->addShrine($world, $saint, $shrineTerritoryId, null, 'tomb', 20);
            $this->growCult($world, $saint, $shrineTerritoryId, 25);
        }

        return $saint->fresh();
    }

    public function openCause(World $world, Character $deceased, string $key, CarbonInterface $date): Saint
    {
        WorldBoundary::assertSameWorld((int) $world->id, (int) $deceased->world_id, 'saint cause');
        $this->policy->assertDead($deceased);

        $existing = Saint::query()->where('character_id', $deceased->id)->first();
        if ($existing) {
            throw new DomainException('A cause is already open for this person.');
        }

        return Saint::query()->create([
            'world_id' => $world->id,
            'character_id' => $deceased->id,
            'key' => $key,
            'name' => $deceased->first_name,
            'origin' => SaintOrigin::CHARACTER,
            'recognition_status' => SaintRecognitionStatus::CAUSE_OPENED,
            'died_date' => optional($deceased->death_date)->toDateString(),
            'reputation' => 5,
        ]);
    }

    public function submitEvidence(
        World $world,
        Saint $saint,
        string $evidenceType,
        CarbonInterface $date,
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?string $notes = null
    ): SaintEvidence {
        WorldBoundary::assertSameWorld((int) $world->id, (int) $saint->world_id, 'saint evidence');
        $weight = $this->policy->weightFor($evidenceType);

        if ($saint->recognition_status === SaintRecognitionStatus::CAUSE_OPENED) {
            $saint->recognition_status = SaintRecognitionStatus::UNDER_INQUIRY;
            $saint->save();
        }

        $evidence = SaintEvidence::query()->create([
            'world_id' => $world->id,
            'saint_id' => $saint->id,
            'evidence_type' => $evidenceType,
            'weight' => $weight,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'recorded_date' => $date->toDateString(),
            'notes' => $notes,
        ]);

        $saint->reputation = min(100, (int) $saint->reputation + intdiv($weight, 3));
        $saint->save();

        return $evidence;
    }

    public function confirmCultus(World $world, Saint $saint, Character $actor, int $territoryId, CarbonInterface $date): Saint
    {
        WorldBoundary::assertSameWorld((int) $world->id, (int) $saint->world_id, 'cultus saint');
        WorldBoundary::assertSameWorld((int) $world->id, (int) $actor->world_id, 'cultus actor');
        if (!$this->authority->mayConfirmCultus($actor)) {
            throw new DomainException('Actor lacks faculty to confirm a local cult.');
        }

        $this->growCult($world, $saint, $territoryId, 25);
        $this->submitEvidence($world, $saint, SaintEvidenceType::LOCAL_CULT, $date, Character::class, $actor->id, 'local cult confirmed');

        if (!SaintRecognitionStatus::isFormal($saint->recognition_status)) {
            $saint->recognition_status = SaintRecognitionStatus::CULTUS;
            $saint->save();
        }

        return $saint->fresh();
    }

    public function recognize(World $world, Saint $saint, Character $actor, CarbonInterface $date): Saint
    {
        WorldBoundary::assertSameWorld((int) $world->id, (int) $saint->world_id, 'recognize saint');
        WorldBoundary::assertSameWorld((int) $world->id, (int) $actor->world_id, 'recognize actor');
        if (!$this->authority->mayRecognizeSaint($actor)) {
            throw new DomainException('Actor lacks faculty to recognize a saint.');
        }

        $subject = $saint->character_id ? Character::query()->find($saint->character_id) : null;
        $this->policy->assertReadyForCanonization($subject, $saint);

        $saint->recognition_status = SaintRecognitionStatus::CANONIZED;
        $saint->recognized_by_character_id = $actor->id;
        $saint->recognized_date = $date->toDateString();
        $saint->reputation = max((int) $saint->reputation, 60);
        $saint->save();

        event(new SaintRecognized($saint->id, $actor->id, $date->toDateString()));

        return $saint->fresh();
    }

    public function addPatronage(World $world, Saint $saint, string $patronageKey, ?string $placeType = null, ?int $placeId = null): SaintPatronage
    {
        return SaintPatronage::query()->create([
            'world_id' => $world->id,
            'saint_id' => $saint->id,
            'patronage_key' => $patronageKey,
            'place_type' => $placeType,
            'place_id' => $placeId,
        ]);
    }

    public function addFeast(World $world, Saint $saint, string $feastDay, string $rank = 'local', ?int $territoryId = null): SaintFeast
    {
        return SaintFeast::query()->create([
            'world_id' => $world->id,
            'saint_id' => $saint->id,
            'feast_day' => $feastDay,
            'rank' => $rank,
            'territory_id' => $territoryId,
        ]);
    }

    public function addShrine(
        World $world,
        Saint $saint,
        ?int $territoryId,
        ?int $holdingId,
        string $kind = 'chapel',
        int $pilgrimageValue = 10
    ): SaintShrine {
        return SaintShrine::query()->create([
            'world_id' => $world->id,
            'saint_id' => $saint->id,
            'territory_id' => $territoryId,
            'holding_id' => $holdingId,
            'shrine_kind' => $kind,
            'pilgrimage_value' => $pilgrimageValue,
        ]);
    }

    public function growCult(World $world, Saint $saint, int $territoryId, int $delta): SaintCult
    {
        $cult = SaintCult::query()
            ->where('saint_id', $saint->id)
            ->where('territory_id', $territoryId)
            ->where('is_current', true)
            ->first();

        if ($cult === null) {
            $cult = SaintCult::query()->create([
                'world_id' => $world->id,
                'saint_id' => $saint->id,
                'territory_id' => $territoryId,
                'intensity' => 0,
                'is_current' => true,
            ]);
        }

        $cult->intensity = max(0, min(100, (int) $cult->intensity + $delta));
        $cult->save();

        return $cult;
    }
}
