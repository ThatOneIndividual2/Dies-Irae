<?php

namespace App\Domain\Catastrophe;

use App\Domain\Enums\ClergyCare;
use App\Domain\Enums\CorpseHandling;
use App\Domain\Enums\PlagueHostType;
use App\Domain\Enums\QuarantineLevel;
use App\Domain\Enums\SocialClass;
use App\Domain\Enums\SpreadVector;
use App\Domain\Population\ApplyMassMortality;
use App\Domain\Population\Settlement;
use App\Domain\Support\BasisPoints;
use App\Domain\Support\IntClamp;

final class PlagueTick
{
    /** @var array<string,int> */
    public const VECTOR_BASE = [
        SpreadVector::ADJACENT => 3500,
        SpreadVector::TRADE => 5500,
        SpreadVector::PILGRIMAGE => 7000,
        SpreadVector::ARMY => 9000,
    ];

    public function progressHost(PlagueFocus $focus, PlagueProfile $profile): array
    {
        $becameInfectious = 0;
        $nextInc = [];
        foreach ($focus->incubationBatches as $batch) {
            $remaining = $batch['remaining'] - 1;
            if ($remaining <= 0) {
                $becameInfectious += $batch['souls'];
            } else {
                $nextInc[] = ['remaining' => $remaining, 'souls' => $batch['souls']];
            }
        }
        $focus->incubationBatches = $nextInc;
        if ($becameInfectious > 0) {
            $focus->addInfectious($becameInfectious, $profile->infectiousTicks);
        }

        $resolved = 0;
        $nextInf = [];
        foreach ($focus->infectiousBatches as $batch) {
            $remaining = $batch['remaining'] - 1;
            if ($remaining <= 0) {
                $resolved += $batch['souls'];
            } else {
                $nextInf[] = ['remaining' => $remaining, 'souls' => $batch['souls']];
            }
        }
        $focus->infectiousBatches = $nextInf;
        $focus->syncCounts();

        return ['became_infectious' => $becameInfectious, 'resolved' => $resolved];
    }

    public function infectSettlement(Settlement $settlement, PlagueProfile $profile, int $localSupernaturalBp): int
    {
        $susceptible = $settlement->susceptible();
        if ($susceptible <= 0 || $settlement->infectious <= 0) {
            return 0;
        }

        $contact = $settlement->contactIntensity();
        $infectiousness = $profile->infectiousness;
        $infectiousness += CorpseHandling::infectiousnessBonus($settlement->corpseHandling);
        $infectiousness += intdiv($settlement->corpsePressure() * $profile->corpseAmplification, 100);
        $infectiousness = BasisPoints::of($infectiousness, $profile->supernaturalAmplification);
        $infectiousness = BasisPoints::of($infectiousness, $localSupernaturalBp);
        $infectiousness = BasisPoints::of(
            $infectiousness,
            BasisPoints::complement(QuarantineLevel::localContactReduction($settlement->quarantine))
        );
        $infectiousness = BasisPoints::of(
            $infectiousness,
            BasisPoints::complement(ClergyCare::civilianRelief($settlement->clergyCare))
        );

        $raw = intdiv($settlement->infectious * $infectiousness, 10000);
        $raw = intdiv($raw * $contact, 10000);
        $new = min($susceptible, max(0, $raw));
        if ($new <= 0 && $settlement->infectious >= 10 && $susceptible >= 10) {
            $new = 1;
        }
        if ($new > 0) {
            $settlement->incubationBatches[] = ['remaining' => $profile->incubationTicks, 'souls' => $new];
            $settlement->syncBurdenFromBatches();
        }

        return $new;
    }

    public function infectHost(PlagueFocus $focus, PlagueProfile $profile): int
    {
        $susceptible = $focus->susceptible();
        if ($susceptible <= 0 || $focus->infectious <= 0) {
            return 0;
        }

        $infectiousness = BasisPoints::of($profile->infectiousness, $profile->supernaturalAmplification);
        $infectiousness = BasisPoints::of($infectiousness, $focus->localSupernaturalBp);
        if ($focus->hostType === PlagueHostType::ARMY || $focus->hostType === PlagueHostType::PILGRIM_COLUMN) {
            $infectiousness = intdiv($infectiousness * 13000, 10000);
        }

        $raw = intdiv($focus->infectious * $infectiousness, 10000);
        $new = min($susceptible, max(0, $raw));
        if ($new <= 0 && $focus->infectious >= 5 && $susceptible >= 5) {
            $new = 1;
        }
        $focus->addIncubating($new, $profile->incubationTicks);

        return $new;
    }

    /**
     * @return array{deaths:int, recovered:int}
     */
    public function resolveCases(Settlement $settlement, PlagueProfile $profile, int $resolved, ApplyMassMortality $mortality, int $localSupernaturalBp): array
    {
        if ($resolved <= 0) {
            return ['deaths' => 0, 'recovered' => 0];
        }

        $mortalityBp = $profile->mortality;
        $mortalityBp = BasisPoints::of($mortalityBp, $profile->supernaturalAmplification);
        $mortalityBp = BasisPoints::of($mortalityBp, $localSupernaturalBp);
        $mortalityBp = BasisPoints::of(
            $mortalityBp,
            BasisPoints::complement(ClergyCare::mortalityRelief($settlement->clergyCare))
        );
        $deaths = min($resolved, BasisPoints::of($resolved, $mortalityBp));
        $recovered = $resolved - $deaths;
        $settlement->recovered += $recovered;
        $settlement->syncBurdenFromBatches();

        $weights = $profile->classMortalityWeights;
        $weights[SocialClass::CLERGY] = ClergyCare::clergyExposureWeight($settlement->clergyCare);
        if ($profile->nobleShelter > 0 && !$profile->supernatural) {
            $weights[SocialClass::NOBLES] = max(20, ($weights[SocialClass::NOBLES] ?? 70) - intdiv($profile->nobleShelter, 100));
        }

        if ($deaths > 0) {
            $mortality->apply($settlement, $deaths, $weights, 'plague');
        }

        return ['deaths' => $deaths, 'recovered' => $recovered];
    }

    public function resolveHostCases(PlagueFocus $focus, PlagueProfile $profile, int $resolved): array
    {
        if ($resolved <= 0) {
            return ['deaths' => 0, 'recovered' => 0];
        }
        $mortalityBp = BasisPoints::of($profile->mortality, $profile->supernaturalAmplification);
        $mortalityBp = BasisPoints::of($mortalityBp, $focus->localSupernaturalBp);
        $deaths = min($resolved, BasisPoints::of($resolved, $mortalityBp));
        $recovered = $resolved - $deaths;
        $focus->dead += $deaths;
        $focus->heads = IntClamp::nonNegative($focus->heads - $deaths);
        $focus->recovered += $recovered;
        $focus->syncCounts();

        return ['deaths' => $deaths, 'recovered' => $recovered];
    }

    public function exportAlongLink(
        Settlement $from,
        Settlement $to,
        SettlementLink $link,
        PlagueProfile $profile
    ): int {
        if (!$link->active || $from->infectious <= 0) {
            return 0;
        }

        $vectorBp = self::VECTOR_BASE[$link->vector] ?? 3000;
        $block = QuarantineLevel::vectorBlock($from->quarantine, $link->vector);
        $block = max($block, QuarantineLevel::vectorBlock($to->quarantine, $link->vector));
        $flow = BasisPoints::of($from->infectious, $link->intensity);
        $flow = BasisPoints::of($flow, $vectorBp);
        $flow = BasisPoints::of($flow, BasisPoints::complement($block));
        $flow = BasisPoints::of($flow, $profile->infectiousness);

        if ($flow <= 0 && $from->infectious >= 20 && $link->intensity >= 4000 && $block < 8000) {
            $flow = 1;
        }

        $room = $to->susceptible();
        $flow = min($flow, $room);
        if ($flow > 0) {
            $to->incubationBatches[] = ['remaining' => $profile->incubationTicks, 'souls' => $flow];
            $to->syncBurdenFromBatches();
        }

        return $flow;
    }

    public function mixArmyAndSettlement(PlagueFocus $army, Settlement $settlement, PlagueProfile $profile): void
    {
        if ($army->locationSettlementId !== $settlement->id) {
            return;
        }

        $toSettlement = intdiv($army->infectious * 5000, 10000);
        $toArmy = intdiv($settlement->infectious * 8000, 10000);
        $block = QuarantineLevel::vectorBlock($settlement->quarantine, SpreadVector::ARMY);
        $toSettlement = BasisPoints::of($toSettlement, BasisPoints::complement($block));
        $toArmy = BasisPoints::of($toArmy, BasisPoints::complement(intdiv($block, 2)));

        if ($toSettlement > 0) {
            $room = $settlement->susceptible();
            $seed = min($room, max(1, $toSettlement));
            if ($seed > 0 && $room > 0) {
                $settlement->incubationBatches[] = ['remaining' => $profile->incubationTicks, 'souls' => $seed];
                $settlement->syncBurdenFromBatches();
            }
        }
        if ($toArmy > 0) {
            $room = $army->susceptible();
            $seed = min($room, max(1, $toArmy));
            if ($seed > 0 && $room > 0) {
                $army->addIncubating($seed, $profile->incubationTicks);
            }
        }
    }
}
