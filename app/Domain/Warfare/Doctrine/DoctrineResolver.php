<?php

namespace App\Domain\Warfare\Doctrine;

use App\Domain\Warfare\Enums\BelligerentKind;
use App\Domain\Warfare\Enums\WarfareKind;
use App\Domain\Warfare\State\Belligerent;
use App\Domain\Warfare\State\WarGoal;

final class DoctrineResolver
{
    public function warKind(Belligerent $aggressor, Belligerent $defender): string
    {
        $kinds = [$aggressor->kind, $defender->kind];

        if (in_array(BelligerentKind::DEMONIC_FACTION, $kinds, true)) {
            return WarfareKind::HUMAN_VS_DEMON;
        }
        if (in_array(BelligerentKind::CORRUPTED_HOST, $kinds, true)) {
            return WarfareKind::CORRUPTED_ARMY;
        }
        if (in_array(BelligerentKind::HOLY_ORDER, $kinds, true)) {
            return WarfareKind::HOLY_ORDER;
        }
        if (in_array(BelligerentKind::CULT, $kinds, true)) {
            return WarfareKind::HUMAN_VS_CULT;
        }

        return WarfareKind::HUMAN_VS_HUMAN;
    }

    public function warProfile(Belligerent $aggressor, Belligerent $defender): WarProfile
    {
        return WarProfile::forKind($this->warKind($aggressor, $defender));
    }

    public function forceProfile(Belligerent $belligerent): ForceProfile
    {
        return ForceProfile::forNature(BelligerentKind::toArmyNature($belligerent->kind));
    }

    public function assertGoalAllowed(WarProfile $profile, WarGoal $goal): void
    {
        if (!$profile->allowsGoal($goal->type)) {
            throw new \InvalidArgumentException(
                "War goal {$goal->type} is not valid for warfare kind {$profile->kind}."
            );
        }
    }
}
