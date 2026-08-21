<?php

namespace App\Actions\Campaign;

use App\Actions\Catastrophe\AdjustTerritoryState;
use App\Models\CampaignGoalProgress;
use App\Models\Character;
use App\Models\ChurchRelation;
use App\Models\GameEvent;
use App\Models\Territory;
use App\Models\World;

final class ApplyCampaignEventChoice
{
    public function __construct(private AdjustTerritoryState $state)
    {
    }

    public function execute(World $world, GameEvent $event, string $option): array
    {
        $family = $event->catalog_family ?: ($event->payload['family'] ?? '');
        $placeKey = $event->payload['territory_key'] ?? null;
        $place = $placeKey
            ? Territory::query()->where('world_id', $world->id)->where('key', $placeKey)->first()
            : null;
        $player = $event->audience_character_id
            ? Character::query()->find($event->audience_character_id)
            : null;

        $effects = ['family' => $family, 'option' => $option];

        if ($place && in_array($family, ['mass_death', 'plague_rumor'], true)) {
            if ($option === 'send_physicians' || $option === 'send_inquiries') {
                $this->adjustTreasury($player, -8);
                $this->church($player, 3);
            }
            if ($option === 'close_roads' || $option === 'halt_eastern') {
                $this->state->addDespair($place, 3);
            }
            if ($option === 'leave_to_parish' || $option === 'close_nothing') {
                $this->state->addCorruption('territory', (int) $place->id, (int) $world->id, 4, 'untended_rumor');
            }
        }

        if ($place && $family === 'refugee_movement') {
            if ($option === 'admit') {
                $this->state->addPopulation($place, 80);
                $this->state->addDespair($place, 4);
            } elseif ($option === 'turn_away') {
                $this->church($player, -4);
                $this->state->addCorruption('territory', (int) $place->id, (int) $world->id, 4, 'refused_refugees');
            } else {
                $this->church($player, 3);
            }
        }

        if ($family === 'papal_taxation') {
            if ($option === 'pay') {
                $this->adjustTreasury($player, -12);
                $this->church($player, 6);
            } elseif ($option === 'refuse') {
                $this->church($player, -8);
            } else {
                $this->church($player, 1);
            }
        }

        if ($family === 'clergy_warning' || $family === 'strange_omen') {
            if (in_array($option, ['permit_procession', 'grant_alms', 'ask_clergy'], true)) {
                $this->church($player, 4);
            }
        }

        if ($family === 'accusation_of_sin' && $option === 'let_crowd' && $place) {
            $this->state->addCorruption('territory', (int) $place->id, (int) $world->id, 5, 'mob_accusation');
        }

        if ($family === 'first_manifestation') {
            if ($option === 'call_clergy') {
                $this->church($player, 5);
                if ($place) {
                    $this->state->addCorruption('territory', (int) $place->id, (int) $world->id, -3, 'clergy_called');
                }
            }
            $this->nudgeGoal($world, $player, 'defeat_local_infernal', 20);
        }

        if ($family === 'political_opportunism' && $option === 'demand_contract') {
            $this->nudgeGoal($world, $player, 'regional_hegemon', 10);
        }
        if ($family === 'mass_death') {
            $this->nudgeGoal($world, $player, 'survive_plague', 15);
        }

        $effects['treasury'] = $player?->fresh()?->treasury;

        return $effects;
    }

    private function adjustTreasury(?Character $player, int $delta): void
    {
        if (!$player) {
            return;
        }
        $player->treasury = max(0, (int) $player->treasury + $delta);
        $player->save();
    }

    private function church(?Character $player, int $delta): void
    {
        if (!$player) {
            return;
        }
        $rel = ChurchRelation::query()->where('character_id', $player->id)->first();
        if (!$rel) {
            return;
        }
        $rel->standing = (int) $rel->standing + $delta;
        $rel->save();
    }

    private function nudgeGoal(World $world, ?Character $player, string $key, int $amount): void
    {
        if (!$player) {
            return;
        }
        $row = CampaignGoalProgress::query()
            ->where('world_id', $world->id)
            ->where('character_id', $player->id)
            ->where('goal_key', $key)
            ->first();
        if (!$row) {
            return;
        }
        $row->progress = min(100, (int) $row->progress + $amount);
        if ($row->progress >= 100) {
            $row->status = 'completed';
        }
        $row->save();
    }
}
