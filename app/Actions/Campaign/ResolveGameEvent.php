<?php

namespace App\Actions\Campaign;

use App\Actions\Army\ResolveBattle;
use App\Actions\Catastrophe\AdjustTerritoryState;
use App\Actions\Events\ResolveCatalogEvent;
use App\Actions\Hell\SetApocalypseStage;
use App\Domain\Campaign\BeatKey;
use App\Domain\Enums\ApocalypseStage;
use App\Domain\Enums\ArmyKind;
use App\Domain\Enums\GameEventStatus;
use App\Domain\Enums\OverlayState;
use App\Domain\Support\Transactional;
use App\Models\Army;
use App\Models\Character;
use App\Models\ChurchRelation;
use App\Models\Cult;
use App\Models\DemonicFaction;
use App\Models\DemonicInfluence;
use App\Models\Displacement;
use App\Models\GameEvent;
use App\Models\Heresy;
use App\Models\HeresyPresence;
use App\Models\Monastery;
use App\Models\PlagueWave;
use App\Models\SupernaturalOverlay;
use App\Models\Territory;
use App\Models\TerritoryPlagueState;
use App\Models\World;
use InvalidArgumentException;
use RuntimeException;

final class ResolveGameEvent
{
    public function __construct(
        private AdjustTerritoryState $state,
        private SetApocalypseStage $apocalypse,
        private ResolveBattle $battle,
        private ResolveCatalogEvent $catalog,
        private ApplyCampaignEventChoice $campaignChoice
    ) {
    }

    public function execute(GameEvent $event, string $option): GameEvent
    {
        if (($event->engine ?? 'campaign_beat') === 'catalog') {
            return $this->catalog->execute($event, $option);
        }

        if ($event->status !== GameEventStatus::AWAITING_DECISION) {
            throw new RuntimeException('This event is not awaiting a decision.');
        }
        $options = $event->options ?? [];
        if (!array_key_exists($option, $options)) {
            throw new InvalidArgumentException("Unknown option {$option} for {$event->event_key}");
        }

        return Transactional::run(function () use ($event, $option) {
            $locked = GameEvent::query()->whereKey($event->id)->lockForUpdate()->firstOrFail();
            $world = World::query()->whereKey($locked->world_id)->lockForUpdate()->firstOrFail();
            $effects = $this->apply($world, $locked, $option);
            $locked->status = GameEventStatus::RESOLVED;
            $locked->chosen_option = $option;
            $locked->effects = $effects;
            $locked->resolved_at = now();
            $locked->save();

            return $locked->fresh();
        });
    }

    private function apply(World $world, GameEvent $event, string $option): array
    {
        $key = $event->event_key;

        return match ($key) {
            BeatKey::PLAGUE_APPEARS => $this->plagueAppears($world, $option),
            BeatKey::REFUGEES_ARRIVE => $this->refugeesArrive($world, $option),
            BeatKey::NOBLE_REFUSES_AID => $this->nobleRefuses($world, $option),
            BeatKey::MONASTERY_REQUESTS_RESOURCES => $this->monasteryRequest($world, $option),
            BeatKey::RUMORS_OF_HERESY => $this->rumors($world, $option),
            BeatKey::CULT_DISCOVERY => $this->cultDiscovery($world, $option),
            BeatKey::DEMONIC_MANIFESTATION => $this->manifestation($world, $option),
            BeatKey::CLERGY_RESPONSE => $this->clergyResponse($world, $option),
            BeatKey::MILITARY_RESPONSE => $this->militaryResponse($world, $option),
            BeatKey::AFTERMATH => $this->aftermath($world, $option),
            default => $this->campaignChoice->execute($world, $event, $option),
        };
    }

    private function plagueAppears(World $world, string $option): array
    {
        $aix = $this->territory($world, 'aix');
        $salon = $this->territory($world, 'salon');
        $wave = PlagueWave::query()->create([
            'world_id' => $world->id,
            'key' => 'great_mortality_1347',
            'name' => 'The Great Mortality',
            'strain' => 'black_death',
            'started_date' => $world->current_date->toDateString(),
            'status' => 'active',
        ]);
        $intensity = match ($option) {
            'close_roads' => 2,
            'send_physicians' => 2,
            'ignore' => 4,
            default => 3,
        };
        TerritoryPlagueState::query()->create([
            'world_id' => $world->id,
            'plague_wave_id' => $wave->id,
            'territory_id' => $aix->id,
            'intensity' => $intensity,
            'arrived_date' => $world->current_date->toDateString(),
            'is_active' => true,
        ]);
        $dead = match ($option) {
            'close_roads' => 180,
            'send_physicians' => 220,
            'ignore' => 400,
            default => 250,
        };
        $this->state->addPopulation($aix, -$dead);
        if ($option === 'close_roads') {
            $this->state->addDespair($salon, 4);
        }
        if ($option === 'send_physicians') {
            $this->adjustTreasury($world, -8);
            $this->churchStanding($world, 3);
        }
        if ($option === 'ignore') {
            $this->state->addCorruption('territory', (int) $aix->id, (int) $world->id, 6, 'untended_plague');
        }
        $this->apocalypse->execute($world, ApocalypseStage::GREAT_MORTALITY, ['plague_in_aix']);

        return ['plague_wave_id' => $wave->id, 'dead_in_aix' => $dead, 'intensity' => $intensity];
    }

    private function refugeesArrive(World $world, string $option): array
    {
        $from = $this->territory($world, 'aix');
        $salon = $this->territory($world, 'salon');
        $abbey = $this->territory($world, 'st_michel');
        $souls = 220;
        $this->state->addPopulation($from, -$souls);
        $destination = $salon;
        if ($option === 'send_to_monastery') {
            $destination = $abbey;
            $monastery = Monastery::query()->where('world_id', $world->id)->firstOrFail();
            $monastery->religious_population += 12;
            $monastery->stores = max(0, $monastery->stores - 6);
            $monastery->save();
            $this->churchStanding($world, 2);
        } elseif ($option === 'turn_away') {
            $destination = $this->territory($world, 'martigues');
            $this->state->addCorruption('territory', (int) $from->id, (int) $world->id, 5, 'refused_refugees');
            $this->churchStanding($world, -4);
            $souls = 180;
        } else {
            $this->state->addDespair($salon, 6);
            $wave = PlagueWave::query()->where('world_id', $world->id)->where('status', 'active')->first();
            if ($wave && !TerritoryPlagueState::query()->where('territory_id', $salon->id)->where('is_active', true)->exists()) {
                TerritoryPlagueState::query()->create([
                    'world_id' => $world->id,
                    'plague_wave_id' => $wave->id,
                    'territory_id' => $salon->id,
                    'intensity' => 1,
                    'arrived_date' => $world->current_date->toDateString(),
                    'is_active' => true,
                ]);
            }
        }
        $this->state->addPopulation($destination, $souls);

        return ['souls' => $souls, 'to' => $destination->key];
    }

    private function nobleRefuses(World $world, string $option): array
    {
        $pel = $this->territory($world, 'pelissanne');
        $salon = $this->territory($world, 'salon');
        $taken = 0;
        if ($option === 'demand_compliance') {
            $taken = min(40, (int) $pel->levy_available);
            $pel->levy_available = (int) $pel->levy_available - $taken;
            $salon->levy_available = (int) $salon->levy_available + $taken;
            $pel->save();
            $salon->save();
        } elseif ($option === 'seize_stores') {
            $food = min(20, (int) $pel->food_stores);
            $pel->food_stores -= $food;
            $salon->food_stores += $food;
            $pel->save();
            $salon->save();
            $this->state->addCorruption('character', (int) $this->ruler($world)->id, (int) $world->id, 8, 'seized_vassal_stores');
            $taken = $food;
        } else {
            $this->state->addDespair($salon, 3);
        }

        return ['option' => $option, 'taken' => $taken];
    }

    private function monasteryRequest(World $world, string $option): array
    {
        $monastery = Monastery::query()->where('world_id', $world->id)->firstOrFail();
        $ruler = $this->ruler($world);
        if ($option === 'grant') {
            $ruler->treasury = max(0, (int) $ruler->treasury - 15);
            $ruler->save();
            $monastery->stores += 12;
            $monastery->save();
            $this->churchStanding($world, 8);
        } elseif ($option === 'refuse') {
            $this->churchStanding($world, -6);
            $this->state->addDespair($this->territory($world, 'st_michel'), 4);
        } else {
            $this->churchStanding($world, 2);
        }

        return ['church_decision' => $option, 'monastery_stores' => $monastery->fresh()->stores];
    }

    private function rumors(World $world, string $option): array
    {
        $heresy = Heresy::query()->where('world_id', $world->id)->firstOrFail();
        $aix = $this->territory($world, 'aix');
        HeresyPresence::query()->updateOrCreate(
            ['heresy_id' => $heresy->id, 'territory_id' => $aix->id],
            [
                'world_id' => $world->id,
                'status' => $option === 'ignore' ? 'suspected' : 'public',
                'started_date' => $world->current_date->toDateString(),
                'is_current' => true,
            ]
        );
        if ($option === 'ask_bishop') {
            $this->churchStanding($world, 4);
        }
        if ($option === 'ignore') {
            $this->state->addCorruption('territory', (int) $aix->id, (int) $world->id, 5, 'ignored_heresy');
        }
        if ($option === 'investigate') {
            Cult::query()->where('world_id', $world->id)->update(['strength' => 2]);
        }

        return ['heresy' => $heresy->key, 'public' => $option !== 'ignore'];
    }

    private function cultDiscovery(World $world, string $option): array
    {
        $cult = Cult::query()->where('world_id', $world->id)->firstOrFail();
        $cult->revealed = true;
        if ($option === 'arrest') {
            $cult->strength = max(1, (int) $cult->strength - 1);
            $this->churchStanding($world, 3);
        } elseif ($option === 'burn') {
            $cult->strength = 0;
            $aix = $this->territory($world, 'aix');
            $this->state->addPopulation($aix, -40);
            $this->state->addCorruption('territory', (int) $aix->id, (int) $world->id, -4, 'burned_cell');
            $this->churchStanding($world, 5);
        } else {
            $cult->strength = (int) $cult->strength + 2;
            $this->state->addCorruption('territory', (int) $cult->territory_id, (int) $world->id, 10, 'concealed_cult');
            $this->churchStanding($world, -8);
        }
        $cult->save();

        return ['cult_revealed' => true, 'cult_strength' => $cult->strength];
    }

    private function manifestation(World $world, string $option): array
    {
        $aix = $this->territory($world, 'aix');
        $overlay = SupernaturalOverlay::query()
            ->where('territory_id', $aix->id)
            ->where('is_current', true)
            ->lockForUpdate()
            ->first();
        if ($overlay) {
            $overlay->is_current = null;
            $overlay->save();
        }
        SupernaturalOverlay::query()->create([
            'world_id' => $world->id,
            'territory_id' => $aix->id,
            'kind' => OverlayState::HAUNTED,
            'changed_on' => $world->current_date->toDateString(),
            'is_current' => true,
        ]);
        $faction = DemonicFaction::query()->where('world_id', $world->id)->firstOrFail();
        $bishop = Character::query()->where('world_id', $world->id)->where('key', 'jacques_aix')->firstOrFail();
        DemonicInfluence::query()->create([
            'world_id' => $world->id,
            'faction_id' => $faction->id,
            'character_id' => $bishop->id,
            'territory_id' => $aix->id,
            'stage' => 'oppression',
            'intensity' => 3,
            'is_active' => true,
            'started_on' => $world->current_date->toDateString(),
        ]);
        $host = Army::query()->create([
            'world_id' => $world->id,
            'name' => 'Shape at the well',
            'kind' => ArmyKind::DEMONIC,
            'territory_id' => $aix->id,
            'strength' => 55,
            'status' => 'idle',
            'is_active' => true,
        ]);
        if ($option === 'evacuate') {
            $this->state->addPopulation($aix, -300);
            $this->state->addPopulation($this->territory($world, 'salon'), 180);
        }
        if ($option === 'call_clergy') {
            $this->churchStanding($world, 4);
        }
        $this->state->addDespair($aix, 12);
        $this->state->addCorruption('territory', (int) $aix->id, (int) $world->id, 12, 'manifestation');
        $this->apocalypse->execute($world, ApocalypseStage::THINNING_VEIL, ['minor_manifestation']);

        return ['overlay' => OverlayState::HAUNTED, 'demonic_army_id' => $host->id, 'option' => $option];
    }

    private function clergyResponse(World $world, string $option): array
    {
        $aix = $this->territory($world, 'aix');
        if ($option === 'exorcism') {
            $this->state->addCorruption('territory', (int) $aix->id, (int) $world->id, -6, 'exorcism');
            $this->state->addDespair($aix, -4);
            $this->churchStanding($world, 6);
            $host = Army::query()->where('world_id', $world->id)->where('kind', ArmyKind::DEMONIC)->where('is_active', true)->first();
            if ($host) {
                $host->strength = max(10, (int) $host->strength - 15);
                $host->save();
            }
        } elseif ($option === 'procession') {
            $this->state->addDespair($aix, -8);
            $this->churchStanding($world, 5);
        } else {
            $this->churchStanding($world, 7);
        }

        return ['clergy_act' => $option];
    }

    private function militaryResponse(World $world, string $option): array
    {
        $ruler = $this->ruler($world);
        $notes = ['option' => $option];
        if ($option === 'attack_manifestation') {
            $host = Army::query()->where('world_id', $world->id)->where('kind', ArmyKind::DEMONIC)->where('is_active', true)->first();
            $playerArmy = Army::query()
                ->where('world_id', $world->id)
                ->where('owner_character_id', $ruler->id)
                ->where('is_active', true)
                ->first();
            if ($host && $playerArmy) {
                $playerArmy->territory_id = $host->territory_id;
                $playerArmy->save();
                $battle = $this->battle->execute($playerArmy->fresh(), $host->fresh(), 'supernatural');
                $notes['battle_id'] = $battle->id;
                $notes['winner'] = $battle->winner;
            } else {
                $notes['skipped'] = 'no_player_army_or_host';
                if ($host) {
                    $host->strength = max(1, (int) $host->strength - 10);
                    $host->save();
                }
            }
        } elseif ($option === 'garrison_capital') {
            $salon = $this->territory($world, 'salon');
            $this->state->addDespair($salon, -5);
        } else {
            $cult = Cult::query()->where('world_id', $world->id)->first();
            if ($cult) {
                $cult->strength = max(0, (int) $cult->strength - 2);
                $cult->save();
                $notes['cult_strength'] = $cult->strength;
            }
        }

        return $notes;
    }

    private function aftermath(World $world, string $option): array
    {
        $aix = $this->territory($world, 'aix');
        $salon = $this->territory($world, 'salon');
        $wave = PlagueWave::query()->where('world_id', $world->id)->where('status', 'active')->first();
        $extraDead = 80;
        if ($wave) {
            $state = TerritoryPlagueState::query()->where('plague_wave_id', $wave->id)->where('territory_id', $aix->id)->first();
            if ($state) {
                $extraDead = 40 * (int) $state->intensity;
                $state->peaked_date = $world->current_date->toDateString();
                $state->save();
            }
        }
        $this->state->addPopulation($aix, -$extraDead);
        $this->state->addCorruption('territory', (int) $salon->id, (int) $world->id, 3, 'aftermath_memory');
        $this->state->addDespair($salon, 2);
        $stage = $world->apocalypseState()->first();

        return [
            'chronicle' => $option,
            'extra_dead_aix' => $extraDead,
            'apocalypse_stage' => $stage?->stage,
            'salon_population' => $salon->fresh()->population,
            'aix_population' => $aix->fresh()->population,
        ];
    }

    private function territory(World $world, string $key): Territory
    {
        return Territory::query()->where('world_id', $world->id)->where('key', $key)->firstOrFail();
    }

    private function ruler(World $world): Character
    {
        return Character::query()->where('world_id', $world->id)->where('key', 'raimond_adhemar')->firstOrFail();
    }

    private function churchStanding(World $world, int $delta): void
    {
        $ruler = $this->ruler($world);
        $rel = ChurchRelation::query()->where('character_id', $ruler->id)->first();
        if (!$rel) {
            return;
        }
        $rel->standing = (int) $rel->standing + $delta;
        $rel->save();
    }

    private function adjustTreasury(World $world, int $delta): void
    {
        $ruler = $this->ruler($world);
        $ruler->treasury = max(0, (int) $ruler->treasury + $delta);
        $ruler->save();
    }
}
