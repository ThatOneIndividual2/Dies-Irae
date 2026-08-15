<?php

namespace App\Domain\Sacred;

use App\Domain\Enums\MiracleCausation;
use App\Domain\Enums\MiracleReading;
use App\Domain\Enums\MiracleStatus;
use App\Domain\Sacred\Policies\MiracleRarityPolicy;
use App\Domain\Sacred\Ports\SacredAuthorityPort;
use App\Domain\Support\WorldBoundary;
use App\Models\Character;
use App\Models\Miracle;
use App\Models\MiracleInterpretation;
use App\Models\MiracleWitness;
use App\Models\Relic;
use App\Models\Saint;
use App\Models\World;
use Carbon\CarbonInterface;
use DomainException;

final class MiracleService
{
    public function __construct(
        private MiracleRarityPolicy $rarity,
        private SacredAuthorityPort $authority
    ) {
    }

    public function claim(
        World $world,
        string $category,
        string $sourceType,
        CarbonInterface $date,
        ?int $sourceId = null,
        ?Saint $saint = null,
        ?Relic $relic = null,
        ?Character $beneficiary = null,
        ?Character $petitioner = null,
        ?string $placeType = null,
        ?int $placeId = null,
        array $witnessCharacterIds = [],
        ?array $metadata = null
    ): Miracle {
        $this->rarity->assertClaimAllowed((int) $world->id, $placeType, $placeId, $category, $sourceType, $date);
        if ($saint) {
            WorldBoundary::assertSameWorld((int) $world->id, (int) $saint->world_id, 'miracle saint');
        }
        if ($relic) {
            WorldBoundary::assertSameWorld((int) $world->id, (int) $relic->world_id, 'miracle relic');
        }

        $miracle = Miracle::query()->create([
            'world_id' => $world->id,
            'category' => $category,
            'status' => MiracleStatus::CLAIMED,
            'causation' => MiracleCausation::UNKNOWN,
            'saint_id' => $saint?->id,
            'relic_id' => $relic?->id,
            'beneficiary_character_id' => $beneficiary?->id,
            'petitioner_character_id' => $petitioner?->id,
            'place_type' => $placeType,
            'place_id' => $placeId,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'occurred_date' => $date->toDateString(),
            'metadata' => $metadata,
        ]);

        MiracleInterpretation::query()->create([
            'world_id' => $world->id,
            'miracle_id' => $miracle->id,
            'interpreter_character_id' => $petitioner?->id,
            'reading' => MiracleReading::UNKNOWN,
            'is_official' => false,
            'recorded_date' => $date->toDateString(),
            'notes' => 'Claim recorded. Causation remains unknown.',
        ]);

        foreach ($witnessCharacterIds as $witnessId) {
            MiracleWitness::query()->create([
                'world_id' => $world->id,
                'miracle_id' => $miracle->id,
                'character_id' => $witnessId,
                'certainty' => 'asserted',
            ]);
        }

        if ($witnessCharacterIds !== []) {
            $miracle->status = MiracleStatus::WITNESSED;
            $miracle->save();
        }

        return $miracle->fresh(['interpretations', 'witnesses']);
    }

    public function interpret(
        World $world,
        Miracle $miracle,
        Character $interpreter,
        string $reading,
        CarbonInterface $date,
        bool $official = false,
        ?string $notes = null
    ): MiracleInterpretation {
        WorldBoundary::assertSameWorld((int) $world->id, (int) $miracle->world_id, 'miracle interpretation');
        WorldBoundary::assertSameWorld((int) $world->id, (int) $interpreter->world_id, 'miracle interpreter');
        if (!in_array($reading, MiracleReading::all(), true)) {
            throw new DomainException("Unknown miracle reading: {$reading}");
        }
        if ($official && !$this->authority->mayRecognizeMiracle($interpreter)) {
            throw new DomainException('Only Church faculties may mark an official reading.');
        }

        $row = MiracleInterpretation::query()->create([
            'world_id' => $world->id,
            'miracle_id' => $miracle->id,
            'interpreter_character_id' => $interpreter->id,
            'reading' => $reading,
            'is_official' => $official,
            'recorded_date' => $date->toDateString(),
            'notes' => $notes,
        ]);

        $readings = MiracleInterpretation::query()
            ->where('miracle_id', $miracle->id)
            ->pluck('reading')
            ->unique()
            ->all();
        if (count($readings) > 1 && $miracle->status !== MiracleStatus::RECOGNIZED) {
            $miracle->status = MiracleStatus::DISPUTED;
            $miracle->save();
        }

        $miracle->causation = MiracleCausation::UNKNOWN;
        $miracle->save();

        return $row;
    }

    public function recognize(World $world, Miracle $miracle, Character $actor, CarbonInterface $date): Miracle
    {
        WorldBoundary::assertSameWorld((int) $world->id, (int) $miracle->world_id, 'miracle recognition');
        if (!$this->authority->mayRecognizeMiracle($actor)) {
            throw new DomainException('Actor lacks faculty to recognize a miracle.');
        }

        MiracleInterpretation::query()
            ->where('miracle_id', $miracle->id)
            ->update(['is_official' => false]);

        MiracleInterpretation::query()->create([
            'world_id' => $world->id,
            'miracle_id' => $miracle->id,
            'interpreter_character_id' => $actor->id,
            'reading' => MiracleReading::DIVINE,
            'is_official' => true,
            'recorded_date' => $date->toDateString(),
            'notes' => 'Church recognition. Causation is still not proven as a mechanic.',
        ]);

        $miracle->status = MiracleStatus::RECOGNIZED;
        $miracle->causation = MiracleCausation::UNKNOWN;
        $miracle->save();

        return $miracle->fresh(['interpretations']);
    }

    public function competingReadings(Miracle $miracle): array
    {
        return MiracleInterpretation::query()
            ->where('miracle_id', $miracle->id)
            ->get(['reading', 'is_official', 'interpreter_character_id'])
            ->toArray();
    }
}
