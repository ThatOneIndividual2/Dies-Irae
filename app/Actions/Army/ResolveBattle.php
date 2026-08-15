<?php

namespace App\Actions\Army;

use App\Domain\Enums\ArmyKind;
use App\Domain\Support\Transactional;
use App\Domain\Support\WorldBoundary;
use App\Models\Army;
use App\Models\Battle;
use App\Models\World;
use RuntimeException;

final class ResolveBattle
{
    public function execute(Army $attacker, Army $defender, string $kind = 'human'): Battle
    {
        WorldBoundary::assertSameWorldEntities('battle', $attacker, $defender);
        if (!$attacker->is_active || !$defender->is_active) {
            throw new RuntimeException('Both armies must still be in the field.');
        }
        if ((int) $attacker->territory_id !== (int) $defender->territory_id) {
            throw new RuntimeException('Armies must occupy the same settlement to fight.');
        }
        if ((int) $attacker->id === (int) $defender->id) {
            throw new RuntimeException('An army cannot fight itself.');
        }

        return Transactional::run(function () use ($attacker, $defender, $kind) {
            $a = Army::query()->whereKey($attacker->id)->lockForUpdate()->firstOrFail();
            $d = Army::query()->whereKey($defender->id)->lockForUpdate()->firstOrFail();
            $world = World::query()->findOrFail($a->world_id);

            $k = $kind === 'supernatural'
                ? (float) config('game.battle.supernatural_k_factor')
                : (float) config('game.battle.human_k_factor');

            $aBefore = (int) $a->strength;
            $dBefore = (int) $d->strength;
            $aLoss = max(1, (int) round($dBefore * $k));
            $dLoss = max(1, (int) round($aBefore * $k));
            if ($d->kind === ArmyKind::DEMONIC) {
                $dLoss = max(1, (int) round($dLoss * 0.7));
                $aLoss = max(1, (int) round($aLoss * 1.15));
            }

            $aAfter = max(0, $aBefore - $aLoss);
            $dAfter = max(0, $dBefore - $dLoss);

            if ($aAfter === $dAfter) {
                $aAfter = max(0, $aAfter - 1);
            }

            $winner = $aAfter > $dAfter ? 'attacker' : 'defender';

            $a->strength = $aAfter;
            $d->strength = $dAfter;
            if ($aAfter < 1) {
                $a->is_active = false;
                $a->status = 'destroyed';
            } else {
                $a->status = 'idle';
            }
            if ($dAfter < 1) {
                $d->is_active = false;
                $d->status = 'destroyed';
            } else {
                $d->status = 'idle';
            }
            $a->save();
            $d->save();

            return Battle::query()->create([
                'world_id' => $a->world_id,
                'territory_id' => $a->territory_id,
                'attacker_army_id' => $a->id,
                'defender_army_id' => $d->id,
                'kind' => $kind,
                'attacker_strength_before' => $aBefore,
                'defender_strength_before' => $dBefore,
                'attacker_strength_after' => $aAfter,
                'defender_strength_after' => $dAfter,
                'winner' => $winner,
                'fought_on' => $world->current_date->toDateString(),
                'notes' => [
                    'attacker_kind' => $a->kind,
                    'defender_kind' => $d->kind,
                ],
            ]);
        });
    }
}
