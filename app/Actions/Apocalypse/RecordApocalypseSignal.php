<?php

namespace App\Actions\Apocalypse;

use App\Contracts\ApocalypseReporter;
use App\Domain\Apocalypse\ApocalypseCatalog;
use App\Domain\Apocalypse\LocalWeatherPolicy;
use App\Domain\Apocalypse\SignalApplier;
use App\Domain\Support\AfterCommit;
use App\Domain\Support\Transactional;
use App\Domain\Support\WorldBoundary;
use App\Events\ApocalypseSignalRecorded;
use App\Models\ApocalypseSignal;
use App\Models\ApocalypseState;
use App\Models\Territory;
use App\Models\TerritorySpiritualWeather;
use App\Models\World;
use InvalidArgumentException;

final class RecordApocalypseSignal implements ApocalypseReporter
{
    public function __construct(
        private ApocalypseCatalog $catalog,
        private SignalApplier $applier,
        private LocalWeatherPolicy $localWeather,
        private EnsureApocalypseState $ensure,
        private ApocalypseStateMutator $mutator
    ) {
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function record(World $world, string $signalKey, int $magnitude, array $context = []): ApocalypseSignal
    {
        return $this->execute($world, $signalKey, $magnitude, $context);
    }

    /**
     * @param  array<string, mixed>  $context
     */
    public function execute(World $world, string $signalKey, int $magnitude, array $context = []): ApocalypseSignal
    {
        if (!$this->catalog->hasSignal($signalKey)) {
            throw new InvalidArgumentException("Unknown apocalypse signal: {$signalKey}");
        }

        $magnitude = max(1, min(100, $magnitude));

        return Transactional::run(function () use ($world, $signalKey, $magnitude, $context) {
            $this->ensure->execute($world);

            $idempotency = $context['idempotency_key'] ?? null;
            if ($idempotency) {
                $existing = ApocalypseSignal::query()
                    ->where('world_id', $world->id)
                    ->where('idempotency_key', $idempotency)
                    ->lockForUpdate()
                    ->first();
                if ($existing) {
                    return $existing;
                }
            }

            $territoryId = isset($context['territory_id']) ? (int) $context['territory_id'] : null;
            if ($territoryId) {
                $territory = Territory::query()->find($territoryId);
                if ($territory) {
                    WorldBoundary::assertSameWorld((int) $world->id, (int) $territory->world_id, 'apocalypse signal territory');
                }
            }

            $state = ApocalypseState::query()
                ->where('world_id', $world->id)
                ->lockForUpdate()
                ->firstOrFail();

            $definition = $this->catalog->signal($signalKey);
            $phase = $this->catalog->phase($state->phase_key);
            $raw = $this->applier->meterDeltas($signalKey, $magnitude);
            $deltas = $this->applier->applyPhaseEfficiency($raw, $phase, $definition['polarity']);

            $row = ApocalypseSignal::query()->create([
                'world_id' => $world->id,
                'signal_key' => $signalKey,
                'polarity' => $definition['polarity'],
                'magnitude' => $magnitude,
                'world_date' => $world->current_date->toDateString(),
                'source_type' => $context['source_type'] ?? null,
                'source_id' => $context['source_id'] ?? null,
                'territory_id' => $territoryId,
                'applied_deltas' => $deltas,
                'payload' => $context['payload'] ?? null,
                'idempotency_key' => $idempotency,
            ]);

            $applied = $this->mutator->applyMeterDeltas($world, $state->fresh(), $deltas, $signalKey);
            $row->applied_deltas = $applied['deltas'];
            $row->save();

            if ($territoryId) {
                $this->applyLocalWeather($world, $territoryId, $signalKey, $magnitude, $state->fresh());
            }

            AfterCommit::dispatch(function () use ($world, $signalKey, $magnitude, $applied) {
                ApocalypseSignalRecorded::dispatch(
                    $world,
                    $signalKey,
                    $magnitude,
                    $applied['deltas'],
                    $world->current_date->toDateString()
                );
            });

            return $row;
        });
    }

    private function applyLocalWeather(
        World $world,
        int $territoryId,
        string $signalKey,
        int $magnitude,
        ApocalypseState $state
    ): void {
        $deltas = $this->applier->localDeltas($signalKey, $magnitude);
        if ($deltas === []) {
            return;
        }

        $weather = TerritorySpiritualWeather::query()
            ->where('world_id', $world->id)
            ->where('territory_id', $territoryId)
            ->lockForUpdate()
            ->first();

        $current = $weather ? $weather->only([
            'local_corruption',
            'local_despair',
            'local_manifestation',
            'local_sanctity',
        ]) : [
            'local_corruption' => $state->global_corruption,
            'local_despair' => $state->despair,
            'local_manifestation' => $state->demonic_manifestation,
            'local_sanctity' => 50,
        ];

        $next = $this->localWeather->applyLocalDeltas(
            $current,
            $deltas,
            $this->catalog->phase($state->phase_key),
            $state->meters(),
            $state->meter_floors ?? []
        );

        TerritorySpiritualWeather::query()->updateOrCreate(
            [
                'world_id' => $world->id,
                'territory_id' => $territoryId,
            ],
            array_merge($next, [
                'last_changed_on' => $world->current_date->toDateString(),
            ])
        );
    }
}
