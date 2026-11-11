<?php

namespace App\Actions\Hell;

use App\Actions\Catastrophe\AdjustTerritoryState;
use App\Domain\Enums\ApocalypseStage;
use App\Domain\Enums\OverlayState;
use App\Domain\Hell\DemonicThreatEngine;
use App\Domain\Hell\Enums\IncursionState;
use App\Domain\Hell\State\TerritoryThreat;
use App\Domain\Hell\State\ThreatWorld;
use App\Domain\Support\Transactional;
use App\Models\ApocalypseState;
use App\Models\CorruptionState;
use App\Models\DespairState;
use App\Models\HellDailyPulse;
use App\Models\SupernaturalOverlay;
use App\Models\Territory;
use App\Models\TerritoryAdjacency;
use App\Models\TerritoryIncursion;
use App\Models\World;

/**
 * One hell overlay pulse per world-day once the veil is thin.
 * The map overlay stays haunted until the engine's own breach rule can fire.
 */
final class PulsePlayableHell
{
    public function __construct(private AdjustTerritoryState $state)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function execute(World $world): array
    {
        return Transactional::run(function () use ($world) {
            $apocalypse = ApocalypseState::query()->where('world_id', $world->id)->first();
            $stage = $apocalypse ? ($apocalypse->getAttributes()['stage'] ?? $apocalypse->phase_key) : null;
            if (!$stage || !ApocalypseStage::atLeast((string) $stage, ApocalypseStage::THINNING_VEIL)) {
                return ['skipped' => true, 'reason' => 'veil_not_thin'];
            }

            $date = $world->current_date->toDateString();
            if (HellDailyPulse::query()->where('world_id', $world->id)->where('pulsed_on', $date)->exists()) {
                return ['skipped' => true, 'reason' => 'already_pulsed'];
            }

            $intensity = $this->intensityFor((string) $stage);
            $threat = $this->build($world, $intensity);
            $before = [];
            foreach ($threat->territories as $id => $territory) {
                $before[$id] = [
                    'corruption' => $territory->corruption,
                    'despair' => $territory->despair,
                    'state' => $territory->incursionState,
                ];
            }

            $engine = DemonicThreatEngine::fromDataDirectory(database_path('data/hell'));
            $result = $engine->pulse($threat);

            $deepened = [];
            foreach ($threat->territories as $key => $territory) {
                $row = Territory::query()->where('world_id', $world->id)->where('key', $key)->first();
                if (!$row) {
                    continue;
                }
                $prior = $before[$key];
                $corruptionDelta = min(8, max(0, $territory->corruption - $prior['corruption']));
                $despairDelta = min(8, max(0, $territory->despair - $prior['despair']));
                if ($corruptionDelta > 0) {
                    $this->state->addCorruption('territory', (int) $row->id, (int) $world->id, $corruptionDelta, 'hell_pulse');
                }
                if ($despairDelta > 0) {
                    $this->state->addDespair($row, $despairDelta);
                }

                if ($territory->incursionState !== IncursionState::DORMANT || $territory->localManifestation > 0) {
                    $this->writeIncursion($world, $row, $territory, $date);
                    $deepened[] = $row->name;
                }

                $this->touchOverlay($world, $row, $territory, $intensity, $date);
            }

            $line = $deepened === []
                ? 'The veil stirred, and no new place answered.'
                : 'The veil stirred over '.implode(', ', $deepened).'.';

            HellDailyPulse::query()->create([
                'world_id' => $world->id,
                'pulsed_on' => $date,
                'notes' => [
                    'line' => $line,
                    'intensity' => $intensity,
                    'transitions' => $result->transitions,
                ],
            ]);

            return ['skipped' => false, 'line' => $line, 'transitions' => $result->transitions];
        });
    }

    private function intensityFor(string $stage): int
    {
        return match ($stage) {
            ApocalypseStage::OPEN_RIFTS => 46,
            ApocalypseStage::COLLAPSE_OF_OFFICES => 60,
            ApocalypseStage::END_OF_THE_AGE => 80,
            ApocalypseStage::THINNING_VEIL => 28,
            default => 10,
        };
    }

    private function build(World $world, int $intensity): ThreatWorld
    {
        $threat = new ThreatWorld();
        $threat->worldId = (int) $world->id;
        $threat->seed = (string) ($world->simulation_seed ?: $world->id);
        $threat->date = $world->current_date->toDateString();
        $threat->apocalypseIntensity = $intensity;

        $territories = Territory::query()->where('world_id', $world->id)->get();
        $keys = [];
        foreach ($territories as $territory) {
            $keys[(int) $territory->id] = (string) $territory->key;
            $overlay = SupernaturalOverlay::query()
                ->where('territory_id', $territory->id)
                ->where('is_current', true)
                ->first();
            $incursion = TerritoryIncursion::query()
                ->where('territory_id', $territory->id)
                ->where('is_current', true)
                ->first();
            $wounded = in_array($overlay->kind ?? null, [
                OverlayState::HAUNTED,
                OverlayState::RIFTED,
                OverlayState::OCCUPIED_BY_HELL,
            ], true);

            $row = new TerritoryThreat((string) $territory->key);
            $row->legalTitleId = 'law:'.$territory->key;
            $row->population = (int) $territory->population;
            $row->corruption = (int) (CorruptionState::query()
                ->where('subject_type', 'territory')
                ->where('subject_id', $territory->id)
                ->value('intensity') ?? 0);
            $row->despair = (int) (DespairState::query()->where('territory_id', $territory->id)->value('intensity') ?? 0);
            $row->incursionState = $incursion->state ?? ($wounded ? IncursionState::MANIFESTED : IncursionState::DORMANT);
            $manifest = (int) ($incursion->local_manifestation ?? ($wounded ? 12 : 0));
            if ($wounded) {
                $manifest = min(100, $manifest + 4);
            }
            $row->localManifestation = $manifest;
            $row->cultActivity = (int) ($incursion->cult_activity ?? 0);
            $threat->addTerritory($row);
        }

        $seen = [];
        foreach (TerritoryAdjacency::query()->where('world_id', $world->id)->get() as $edge) {
            $a = $keys[(int) $edge->from_territory_id] ?? null;
            $b = $keys[(int) $edge->to_territory_id] ?? null;
            if (!$a || !$b) {
                continue;
            }
            $pair = strcmp($a, $b) < 0 ? $a.'|'.$b : $b.'|'.$a;
            if (isset($seen[$pair])) {
                continue;
            }
            $seen[$pair] = true;
            $threat->link($a, $b);
        }

        return $threat;
    }

    private function writeIncursion(World $world, Territory $territory, TerritoryThreat $threat, string $date): void
    {
        $current = TerritoryIncursion::query()
            ->where('territory_id', $territory->id)
            ->where('is_current', true)
            ->lockForUpdate()
            ->first();

        $payload = [
            'state' => $threat->incursionState,
            'corruption' => min(100, $threat->corruption),
            'local_manifestation' => min(100, $threat->localManifestation),
            'cult_activity' => min(100, $threat->cultActivity),
            'settlement_corrupted' => $threat->settlementCorrupted,
            'changed_on' => $date,
            'is_current' => true,
        ];

        if ($current) {
            $current->fill($payload);
            $current->save();

            return;
        }

        TerritoryIncursion::query()->create(array_merge($payload, [
            'world_id' => $world->id,
            'territory_id' => $territory->id,
        ]));
    }

    private function touchOverlay(World $world, Territory $territory, TerritoryThreat $threat, int $intensity, string $date): void
    {
        $overlay = SupernaturalOverlay::query()
            ->where('territory_id', $territory->id)
            ->where('is_current', true)
            ->lockForUpdate()
            ->first();
        if (!$overlay) {
            return;
        }

        $promote = $intensity >= 40
            && IncursionState::isAtLeast($threat->incursionState, IncursionState::BREACHED)
            && $overlay->kind === OverlayState::HAUNTED;

        if ($promote) {
            SupernaturalOverlay::query()->whereKey($overlay->id)->update(['is_current' => null]);
            SupernaturalOverlay::query()->create([
                'world_id' => $world->id,
                'territory_id' => $territory->id,
                'kind' => OverlayState::RIFTED,
                'intensity' => min(100, (int) $overlay->intensity + 1),
                'changed_on' => $date,
                'is_current' => true,
            ]);

            return;
        }

        if (in_array($overlay->kind, [OverlayState::HAUNTED, OverlayState::RIFTED, OverlayState::OCCUPIED_BY_HELL], true)) {
            $overlay->intensity = min(100, (int) $overlay->intensity + 1);
            $overlay->save();
        }
    }
}
