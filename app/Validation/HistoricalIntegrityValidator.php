<?php

namespace App\Validation;

use App\Domain\Enums\SpiritualOfficeRank;
use App\Domain\Enums\TitleRank;
use App\Models\Character;
use App\Models\ChurchProvince;
use App\Models\Papacy;
use App\Models\PilgrimageSite;
use App\Models\PoliticalRelation;
use App\Models\Realm;
use App\Models\See;
use App\Models\SpiritualOffice;
use App\Models\SpiritualOfficeHoldership;
use App\Models\Territory;
use App\Models\TerritoryPlagueState;
use App\Models\Title;
use App\Models\TitleOwnership;
use App\Models\TradeCorridor;
use App\Models\VassalRelationship;
use App\Models\World;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class HistoricalIntegrityValidator
{
    public const MIN_TERRITORIES = 50;
    public const MIN_REALMS = 15;
    public const MAX_CITY_POPULATION = 400000;
    public const MAX_INFECTED_SHARE = 0.12;

    public const REQUIRED_PLAGUE_KEYS = ['caffa', 'constantinople', 'messina'];

    public const MUST_REMAIN_CLEAR = [
        'london',
        'paris',
        'cologne',
        'prague',
        'krakow',
        'buda',
        'toledo',
        'copenhagen',
        'edinburgh',
    ];

    public function validate(World $world, ValidationReport $report): void
    {
        $this->validateYear($world, $report);
        $this->validateScope($world, $report);
        $this->validateTitleHierarchy($world, $report);
        $this->validateMissingRulers($world, $report);
        $this->validateDisconnectedSettlements($world, $report);
        $this->validateChurchHierarchy($world, $report);
        $this->validatePopulationAnomalies($world, $report);
        $this->validateRealmMemberships($world, $report);
        $this->validateDates($world, $report);
        $this->validatePlagueState($world, $report);
        $this->validateAvignonPapacy($world, $report);
        $this->validateHistoricalLayers($world, $report);
    }

    private function worldDate(World $world): ?string
    {
        $date = $world->current_date ?? $world->game_date ?? $world->start_date;

        return $date ? $date->toDateString() : null;
    }

    private function validateYear(World $world, ValidationReport $report): void
    {
        $year = $world->start_date ? (int) $world->start_date->format('Y') : 0;
        if ($year !== 1347) {
            $report->add(new ValidationIssue(
                'historical.year',
                "Historical start year is {$year}, expected 1347.",
                'error',
                'world',
                (int) $world->id
            ));
        }
    }

    private function validateScope(World $world, ValidationReport $report): void
    {
        $territories = Territory::query()->where('world_id', $world->id)->count();
        if ($territories < self::MIN_TERRITORIES) {
            $report->add(new ValidationIssue(
                'historical.scope',
                "Historical map has {$territories} territories, expected at least ".self::MIN_TERRITORIES.'.',
                'error',
                'world',
                (int) $world->id
            ));
        }

        $realms = Realm::query()->where('world_id', $world->id)->count();
        if ($realms < self::MIN_REALMS) {
            $report->add(new ValidationIssue(
                'historical.realms',
                "Historical map has {$realms} realms, expected at least ".self::MIN_REALMS.'.',
                'error',
                'world',
                (int) $world->id
            ));
        }
    }

    private function validateTitleHierarchy(World $world, ValidationReport $report): void
    {
        $titles = Title::query()->where('world_id', $world->id)->get()->keyBy('id');

        foreach ($titles as $title) {
            foreach (['parent_title_id' => 'parent', 'de_jure_liege_title_id' => 'de_jure'] as $column => $kind) {
                $parentId = $title->{$column} ?? null;
                if (! $parentId) {
                    continue;
                }
                $parent = $titles->get((int) $parentId);
                if ($parent === null) {
                    $report->add(new ValidationIssue(
                        'title.missing_'.$kind,
                        "Title {$title->key} {$kind} is missing or cross-world.",
                        'error',
                        'title',
                        (int) $title->id
                    ));
                    continue;
                }
                if (TitleRank::weight((string) $parent->rank) <= TitleRank::weight((string) $title->rank)) {
                    $report->add(new ValidationIssue(
                        'title.'.$kind.'_rank',
                        "Title {$title->key} ({$title->rank}) cannot sit under {$parent->key} ({$parent->rank}).",
                        'error',
                        'title',
                        (int) $title->id
                    ));
                }
            }

            $this->detectTitleCycle($title, $titles, 'parent_title_id', 'title.parent_cycle', $report);
            $this->detectTitleCycle($title, $titles, 'de_jure_liege_title_id', 'title.de_jure_cycle', $report);
        }
    }

    private function detectTitleCycle(Title $start, $titles, string $column, string $code, ValidationReport $report): void
    {
        $seen = [];
        $current = $start;
        $guard = 0;
        while ($current && $guard < 64) {
            $guard++;
            if (isset($seen[$current->id])) {
                $report->add(new ValidationIssue(
                    $code,
                    "Title cycle involving {$start->key}.",
                    'error',
                    'title',
                    (int) $start->id
                ));
                return;
            }
            $seen[$current->id] = true;
            $parentId = $current->{$column} ?? null;
            if (! $parentId) {
                return;
            }
            $current = $titles->get((int) $parentId);
        }
    }

    private function validateMissingRulers(World $world, ValidationReport $report): void
    {
        Title::query()
            ->where('world_id', $world->id)
            ->whereIn('rank', [TitleRank::KINGDOM, TitleRank::EMPIRE])
            ->each(function (Title $title) use ($report) {
                $owner = TitleOwnership::query()
                    ->where('title_id', $title->id)
                    ->where('is_current', 1)
                    ->first();
                if ($owner === null) {
                    $report->add(new ValidationIssue(
                        'title.missing_ruler',
                        "Kingdom or empire {$title->key} has no current holder.",
                        'error',
                        'title',
                        (int) $title->id
                    ));
                }
            });
    }

    private function validateDisconnectedSettlements(World $world, ValidationReport $report): void
    {
        $territories = Territory::query()->where('world_id', $world->id)->get(['id', 'key']);
        if ($territories->count() < 2) {
            return;
        }

        if (! Schema::hasTable('territory_adjacencies')) {
            $report->add(new ValidationIssue(
                'settlement.disconnected',
                'Territory adjacency table is missing; the map cannot be proven connected.',
                'error',
                'world',
                (int) $world->id
            ));
            return;
        }

        $ids = $territories->pluck('id')->map(fn ($id) => (int) $id)->all();
        $graph = [];
        foreach ($ids as $id) {
            $graph[$id] = [];
        }

        $edges = DB::table('territory_adjacencies')->where('world_id', $world->id)->get();
        foreach ($edges as $edge) {
            $from = (int) $edge->from_territory_id;
            $to = (int) $edge->to_territory_id;
            if (! isset($graph[$from]) || ! isset($graph[$to])) {
                continue;
            }
            $graph[$from][$to] = true;
            $graph[$to][$from] = true;
        }

        $start = $ids[0];
        $queue = [$start];
        $seen = [$start => true];
        while ($queue !== []) {
            $node = array_shift($queue);
            foreach (array_keys($graph[$node]) as $next) {
                if (! isset($seen[$next])) {
                    $seen[$next] = true;
                    $queue[] = $next;
                }
            }
        }

        if (count($seen) < count($ids)) {
            $orphans = $territories->filter(fn ($t) => ! isset($seen[(int) $t->id]))->pluck('key')->take(8)->implode(', ');
            $report->add(new ValidationIssue(
                'settlement.disconnected',
                'Territory graph is not connected. Isolated keys include: '.$orphans.'.',
                'error',
                'world',
                (int) $world->id
            ));
        }
    }

    private function validateChurchHierarchy(World $world, ValidationReport $report): void
    {
        $sees = See::query()->where('world_id', $world->id)->get()->keyBy('id');
        $offices = SpiritualOffice::query()->where('world_id', $world->id)->get();

        foreach ($sees as $see) {
            $parentId = $see->parent_see_id ?? null;
            if (! $parentId) {
                continue;
            }
            $parent = $sees->get((int) $parentId);
            if ($parent === null || (int) $parent->world_id !== (int) $see->world_id) {
                $report->add(new ValidationIssue(
                    'church.see_parent_world_mismatch',
                    "See {$see->key} has a missing or cross-world parent.",
                    'error',
                    'see',
                    (int) $see->id
                ));
                continue;
            }
        }

        foreach ($sees as $see) {
            $seen = [];
            $current = $see;
            $guard = 0;
            while ($current && $guard < 32) {
                $guard++;
                if (isset($seen[$current->id])) {
                    $report->add(new ValidationIssue(
                        'church.see_cycle',
                        "See cycle involving {$see->key}.",
                        'error',
                        'see',
                        (int) $see->id
                    ));
                    break;
                }
                $seen[$current->id] = true;
                if (! $current->parent_see_id) {
                    break;
                }
                $current = $sees->get((int) $current->parent_see_id);
            }
        }

        $popeOffices = $offices->filter(fn (SpiritualOffice $o) => $o->rank === SpiritualOfficeRank::POPE && $o->is_active !== false);
        if ($popeOffices->count() !== 1) {
            $report->add(new ValidationIssue(
                'church.pope_office_count',
                'Historical Latin church must have exactly one papal office. Found '.$popeOffices->count().'.',
                'error',
                'world',
                (int) $world->id
            ));
        }

        $dupRanks = [];
        foreach ($offices as $office) {
            if (! $office->see_id) {
                continue;
            }
            $stamp = $office->see_id.':'.$office->rank;
            if (isset($dupRanks[$stamp])) {
                $report->add(new ValidationIssue(
                    'church.duplicate_office',
                    "See {$office->see_id} has more than one {$office->rank} office.",
                    'error',
                    'spiritual_office',
                    (int) $office->id
                ));
            }
            $dupRanks[$stamp] = true;
        }

        if (Schema::hasColumn('church_provinces', 'metropolitan_see_id')) {
            ChurchProvince::query()->where('world_id', $world->id)->each(function (ChurchProvince $province) use ($sees, $report) {
                if (! $province->metropolitan_see_id) {
                    return;
                }
                $metro = $sees->get((int) $province->metropolitan_see_id);
                if ($metro === null || (int) $metro->church_province_id !== (int) $province->id) {
                    $report->add(new ValidationIssue(
                        'church.metropolitan_mismatch',
                        "Province {$province->key} metropolitan does not belong to that province.",
                        'error',
                        'church_province',
                        (int) $province->id
                    ));
                }
            });
        }
    }

    private function validatePopulationAnomalies(World $world, ValidationReport $report): void
    {
        $capitalIds = Title::query()
            ->where('world_id', $world->id)
            ->whereIn('rank', [TitleRank::KINGDOM, TitleRank::EMPIRE])
            ->pluck('capital_territory_id')
            ->filter()
            ->all();

        Territory::query()->where('world_id', $world->id)->each(function (Territory $territory) use ($report, $capitalIds) {
            $pop = (int) $territory->population;
            if ($pop > self::MAX_CITY_POPULATION) {
                $report->add(new ValidationIssue(
                    'population.implausible',
                    "Territory {$territory->key} population {$pop} exceeds the documented city ceiling.",
                    'error',
                    'territory',
                    (int) $territory->id
                ));
            }
            if ($pop === 0 && in_array($territory->id, $capitalIds, false)) {
                $report->add(new ValidationIssue(
                    'population.empty_capital',
                    "Kingdom or empire capital {$territory->key} has no population.",
                    'error',
                    'territory',
                    (int) $territory->id
                ));
            }
            if (Schema::hasColumn('territories', 'agricultural_capacity')) {
                $agri = (int) ($territory->agricultural_capacity ?? 0);
                if ($agri < 0 || $agri > 100) {
                    $report->add(new ValidationIssue(
                        'population.agriculture',
                        "Territory {$territory->key} agricultural capacity {$agri} is out of 0-100.",
                        'error',
                        'territory',
                        (int) $territory->id
                    ));
                }
            }
        });
    }

    private function validateRealmMemberships(World $world, ValidationReport $report): void
    {
        Realm::query()->where('world_id', $world->id)->each(function (Realm $realm) use ($world, $report) {
            $title = Title::query()->find($realm->primary_title_id);
            if ($title === null || (int) $title->world_id !== (int) $world->id) {
                $report->add(new ValidationIssue(
                    'realm.invalid_primary_title',
                    "Realm {$realm->key} primary title is missing or cross-world.",
                    'error',
                    'realm',
                    (int) $realm->id
                ));
                return;
            }

            $held = TitleOwnership::query()
                ->where('title_id', $realm->primary_title_id)
                ->where('holder_character_id', $realm->top_liege_character_id)
                ->where('is_current', 1)
                ->exists();
            if (! $held) {
                $report->add(new ValidationIssue(
                    'realm.liege_missing_primary',
                    "Realm {$realm->key} top liege does not currently hold the primary title.",
                    'error',
                    'realm',
                    (int) $realm->id
                ));
            }
        });

        VassalRelationship::query()->where('world_id', $world->id)->where('is_current', 1)->each(function (VassalRelationship $rel) use ($world, $report) {
            if ($rel->realm_id) {
                $realm = Realm::query()->find($rel->realm_id);
                if ($realm === null || (int) $realm->world_id !== (int) $world->id) {
                    $report->add(new ValidationIssue(
                        'realm.invalid_vassal_membership',
                        "Vassal {$rel->vassal_character_id} points at a missing or cross-world realm.",
                        'error',
                        'character',
                        (int) $rel->vassal_character_id
                    ));
                }
            }
        });
    }

    private function validateDates(World $world, ValidationReport $report): void
    {
        $worldDate = $this->worldDate($world);
        if ($worldDate === null) {
            $report->add(new ValidationIssue(
                'date.missing_world_date',
                'World has no current date.',
                'error',
                'world',
                (int) $world->id
            ));
            return;
        }

        Character::query()->where('world_id', $world->id)->each(function (Character $character) use ($worldDate, $report) {
            if ($character->birth_date && $character->birth_date->toDateString() > $worldDate) {
                $report->add(new ValidationIssue(
                    'date.future_birth',
                    "Character {$character->key} is born after the world date.",
                    'error',
                    'character',
                    (int) $character->id
                ));
            }
        });

        TitleOwnership::query()->where('world_id', $world->id)->where('is_current', 1)->each(function (TitleOwnership $own) use ($worldDate, $report) {
            $acquired = optional($own->acquired_date)->toDateString();
            if ($acquired && $acquired > $worldDate) {
                $report->add(new ValidationIssue(
                    'date.future_title',
                    "Title ownership {$own->id} is dated after the world date.",
                    'error',
                    'title',
                    (int) $own->title_id
                ));
            }
            $holder = Character::query()->find($own->holder_character_id);
            if ($holder && $holder->birth_date && $acquired && $acquired < $holder->birth_date->toDateString()) {
                $report->add(new ValidationIssue(
                    'date.title_before_birth',
                    "Title ownership {$own->id} predates the holder's birth.",
                    'error',
                    'title',
                    (int) $own->title_id
                ));
            }
        });

        SpiritualOfficeHoldership::query()->where('world_id', $world->id)->where('is_current', 1)->each(function (SpiritualOfficeHoldership $own) use ($worldDate, $report) {
            $acquired = optional($own->acquired_date)->toDateString();
            if ($acquired && $acquired > $worldDate) {
                $report->add(new ValidationIssue(
                    'date.future_office',
                    "Office holdership {$own->id} is dated after the world date.",
                    'error',
                    'spiritual_office',
                    (int) $own->spiritual_office_id
                ));
            }
        });

        if (Schema::hasTable('territory_plague_states')) {
            TerritoryPlagueState::query()->where('world_id', $world->id)->each(function (TerritoryPlagueState $state) use ($worldDate, $report) {
                $arrived = $state->arrived_on;
                $arrivedString = $arrived instanceof \DateTimeInterface ? $arrived->format('Y-m-d') : (is_string($arrived) ? $arrived : null);
                if ($arrivedString && $arrivedString > $worldDate) {
                    $report->add(new ValidationIssue(
                        'date.future_plague',
                        "Plague arrival on territory {$state->territory_id} is after the world date.",
                        'error',
                        'territory',
                        (int) $state->territory_id
                    ));
                }
            });
        }

        if (Schema::hasTable('political_relations')) {
            PoliticalRelation::query()->where('world_id', $world->id)->each(function (PoliticalRelation $rel) use ($worldDate, $report) {
                if ($rel->started_date && $rel->started_date->toDateString() > $worldDate) {
                    $report->add(new ValidationIssue(
                        'date.future_relation',
                        "Political relation {$rel->name} starts after the world date.",
                        'error',
                        'realm',
                        (int) $rel->from_realm_id
                    ));
                }
            });
        }
    }

    private function validatePlagueState(World $world, ValidationReport $report): void
    {
        $territoryCount = Territory::query()->where('world_id', $world->id)->count();
        $infected = TerritoryPlagueState::query()->where('world_id', $world->id)->get();
        if (Schema::hasColumn('territory_plague_states', 'is_active')) {
            $infected = $infected->filter(fn ($row) => (bool) $row->is_active);
        }

        if ($territoryCount > 0 && ($infected->count() / $territoryCount) > self::MAX_INFECTED_SHARE) {
            $report->add(new ValidationIssue(
                'historical.plague_too_wide',
                'Too many territories are already infected for an October 1347 opening.',
                'error',
                'world',
                (int) $world->id
            ));
        }

        $keys = Territory::query()
            ->whereIn('id', $infected->pluck('territory_id')->all() ?: [0])
            ->pluck('key')
            ->all();

        foreach (self::REQUIRED_PLAGUE_KEYS as $key) {
            if (! in_array($key, $keys, true)) {
                $report->add(new ValidationIssue(
                    'historical.plague_entry',
                    "Required 1347 plague entry {$key} is not infected.",
                    'error',
                    'world',
                    (int) $world->id
                ));
            }
        }

        $clearHits = Territory::query()
            ->where('world_id', $world->id)
            ->whereIn('key', self::MUST_REMAIN_CLEAR)
            ->whereIn('id', $infected->pluck('territory_id')->all() ?: [0])
            ->pluck('key')
            ->all();
        foreach ($clearHits as $key) {
            $report->add(new ValidationIssue(
                'historical.plague_west',
                "Western or interior capital {$key} should still be clear in October 1347.",
                'error',
                'world',
                (int) $world->id
            ));
        }
    }

    private function validateAvignonPapacy(World $world, ValidationReport $report): void
    {
        $papacy = Papacy::query()->where('world_id', $world->id)->first();
        if ($papacy === null) {
            $report->add(new ValidationIssue(
                'historical.avignon_papacy',
                'No papacy row exists for this world.',
                'error',
                'world',
                (int) $world->id
            ));
            return;
        }

        $see = See::query()->find($papacy->papal_see_id);
        $territory = $see ? Territory::query()->find($see->territory_id) : null;
        $seatOk = $see && (
            $see->key === 'see-avignon'
            || ($territory && $territory->key === 'avignon')
        );
        if (! $seatOk) {
            $report->add(new ValidationIssue(
                'historical.avignon_papacy',
                'Papal seat is not Avignon. In 1347 Clement VI resides at Avignon, not Rome.',
                'error',
                'papacy',
                (int) $papacy->id
            ));
        }
    }

    private function validateHistoricalLayers(World $world, ValidationReport $report): void
    {
        if (Schema::hasTable('trade_corridors') && TradeCorridor::query()->where('world_id', $world->id)->count() < 1) {
            $report->add(new ValidationIssue(
                'historical.trade',
                'Historical seed has no trade corridors.',
                'error',
                'world',
                (int) $world->id
            ));
        }

        if (Schema::hasTable('pilgrimage_sites') && PilgrimageSite::query()->where('world_id', $world->id)->count() < 1) {
            $report->add(new ValidationIssue(
                'historical.pilgrimage',
                'Historical seed has no pilgrimage sites.',
                'error',
                'world',
                (int) $world->id
            ));
        }
    }
}
