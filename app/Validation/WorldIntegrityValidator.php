<?php

namespace App\Validation;

use App\Domain\Enums\SpiritualOfficeRank;
use App\Domain\Enums\TitleRank;
use App\Models\Character;
use App\Models\ChurchProvince;
use App\Models\CorruptionState;
use App\Models\DemonicFaction;
use App\Models\DemonicThreat;
use App\Models\Holding;
use App\Models\Monastery;
use App\Models\PlagueWave;
use App\Models\Realm;
use App\Models\See;
use App\Models\SpiritualOffice;
use App\Models\SpiritualOfficeHoldership;
use App\Models\Territory;
use App\Models\Title;
use App\Models\TitleOwnership;
use App\Models\VassalRelationship;
use App\Models\World;
use Illuminate\Support\Facades\DB;

class WorldIntegrityValidator
{
    public function validate(World $world, bool $historical = false): ValidationReport
    {
        $report = new ValidationReport((int) $world->id);

        $this->validateDatabaseIsolation($report);
        $this->validateTitleRanksAreSecular($world, $report);
        $this->validateSpiritualOfficesAreNotTitles($world, $report);
        $this->validateCurrentOwnershipUniqueness($world, $report);
        $this->validateVassalChains($world, $report);
        $this->validatePopulation($world, $report);
        $this->validateDemonicFactionsAreNotRealms($world, $report);
        $this->validateChurchProvinceGraph($world, $report);

        if ($historical) {
            (new HistoricalIntegrityValidator())->validate($world, $report);
        }

        return $report;
    }

    private function validateDatabaseIsolation(ValidationReport $report): void
    {
        $current = (string) config('database.connections.'.config('database.default').'.database');
        $forbidden = config('game.isolation.forbidden_databases', []);

        if (in_array($current, $forbidden, true)) {
            $report->add(new ValidationIssue(
                'isolation.forbidden_database',
                "Connected to forbidden sibling database {$current}.",
                'error'
            ));
        }
    }

    private function validateTitleRanksAreSecular(World $world, ValidationReport $report): void
    {
        $spiritual = SpiritualOfficeRank::all();

        Title::query()->where('world_id', $world->id)->orderBy('id')->each(function (Title $title) use ($report, $spiritual) {
            if (in_array($title->rank, $spiritual, true)) {
                $report->add(new ValidationIssue(
                    'separation.spiritual_title_rank',
                    "Title {$title->id} ({$title->key}) uses spiritual rank {$title->rank}.",
                    'error',
                    'title',
                    (int) $title->id
                ));
            }

            if (! TitleRank::isSecular((string) $title->rank)) {
                $report->add(new ValidationIssue(
                    'title.unknown_rank',
                    "Title {$title->id} has unknown rank {$title->rank}.",
                    'error',
                    'title',
                    (int) $title->id
                ));
            }
        });
    }

    private function validateSpiritualOfficesAreNotTitles(World $world, ValidationReport $report): void
    {
        $titleKeys = Title::query()->where('world_id', $world->id)->pluck('key')->all();

        SpiritualOffice::query()->where('world_id', $world->id)->each(function (SpiritualOffice $office) use ($report, $titleKeys) {
            if (in_array($office->key, $titleKeys, true)) {
                $report->add(new ValidationIssue(
                    'separation.office_key_collides_with_title',
                    "Spiritual office {$office->key} shares a key with a secular title.",
                    'error',
                    'spiritual_office',
                    (int) $office->id
                ));
            }

            if (! SpiritualOfficeRank::isSpiritual((string) $office->rank)) {
                $report->add(new ValidationIssue(
                    'church.unknown_office_rank',
                    "Spiritual office {$office->id} has unknown rank {$office->rank}.",
                    'error',
                    'spiritual_office',
                    (int) $office->id
                ));
            }
        });
    }

    private function validateCurrentOwnershipUniqueness(World $world, ValidationReport $report): void
    {
        $dupTitles = TitleOwnership::query()
            ->select('title_id', DB::raw('COUNT(*) as c'))
            ->where('world_id', $world->id)
            ->where('is_current', 1)
            ->groupBy('title_id')
            ->having('c', '>', 1)
            ->get();

        foreach ($dupTitles as $row) {
            $report->add(new ValidationIssue(
                'title.multiple_current_owners',
                "Title {$row->title_id} has more than one current owner.",
                'error',
                'title',
                (int) $row->title_id
            ));
        }

        $dupOffices = SpiritualOfficeHoldership::query()
            ->select('spiritual_office_id', DB::raw('COUNT(*) as c'))
            ->where('world_id', $world->id)
            ->where('is_current', 1)
            ->groupBy('spiritual_office_id')
            ->having('c', '>', 1)
            ->get();

        foreach ($dupOffices as $row) {
            $report->add(new ValidationIssue(
                'church.multiple_current_holders',
                "Spiritual office {$row->spiritual_office_id} has more than one current holder.",
                'error',
                'spiritual_office',
                (int) $row->spiritual_office_id
            ));
        }
    }

    private function validateVassalChains(World $world, ValidationReport $report): void
    {
        $links = VassalRelationship::query()
            ->where('world_id', $world->id)
            ->where('is_current', 1)
            ->get()
            ->keyBy('vassal_character_id');

        foreach ($links as $rel) {
            $seen = [];
            $current = (int) $rel->vassal_character_id;
            $guard = 0;

            while ($guard < 64) {
                $guard++;
                if (isset($seen[$current])) {
                    $report->add(new ValidationIssue(
                        'vassal.cycle',
                        "Vassal chain cycle involving character {$rel->vassal_character_id}.",
                        'error',
                        'character',
                        (int) $rel->vassal_character_id
                    ));
                    break;
                }
                $seen[$current] = true;

                $link = $links->get($current);
                if ($link === null) {
                    break;
                }

                $current = (int) $link->liege_character_id;
            }
        }
    }

    private function validatePopulation(World $world, ValidationReport $report): void
    {
        Territory::query()->where('world_id', $world->id)->where('population', '<', 0)->each(function (Territory $t) use ($report) {
            $report->add(new ValidationIssue(
                'population.negative',
                "Territory {$t->id} has negative population.",
                'error',
                'territory',
                (int) $t->id
            ));
        });
    }

    private function validateDemonicFactionsAreNotRealms(World $world, ValidationReport $report): void
    {
        $realmKeys = Realm::query()->where('world_id', $world->id)->pluck('key')->all();

        DemonicFaction::query()->where('world_id', $world->id)->each(function (DemonicFaction $faction) use ($report, $realmKeys) {
            if (in_array($faction->key, $realmKeys, true)) {
                $report->add(new ValidationIssue(
                    'separation.demon_faction_is_realm',
                    "Demonic faction {$faction->key} shares a key with a realm.",
                    'error',
                    'demonic_faction',
                    (int) $faction->id
                ));
            }
        });
    }

    private function validateChurchProvinceGraph(World $world, ValidationReport $report): void
    {
        See::query()->where('world_id', $world->id)->each(function (See $see) use ($report) {
            $province = ChurchProvince::query()->find($see->church_province_id);
            if ($province === null || (int) $province->world_id !== (int) $see->world_id) {
                $report->add(new ValidationIssue(
                    'church.see_province_world_mismatch',
                    "See {$see->id} points at a missing or cross-world province.",
                    'error',
                    'see',
                    (int) $see->id
                ));
            }
        });
    }
}
