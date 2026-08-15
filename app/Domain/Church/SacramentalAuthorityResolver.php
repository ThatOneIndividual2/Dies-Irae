<?php

namespace App\Domain\Church;

use App\Domain\Enums\ClergyGrade;
use App\Domain\Enums\SacramentKind;
use App\Models\Character;
use App\Models\ExtraordinarySpiritualAuthorization;
use App\Models\SacramentalAuthority;
use Carbon\CarbonInterface;

final class SacramentalAuthorityResolver
{
    public function __construct(private ClergyLegitimacy $legitimacy)
    {
    }

    public function canPerform(Character $minister, string $sacramentKind, ?CarbonInterface $onDate = null): bool
    {
        if ($this->legitimacy->isExcommunicated($minister)) {
            return false;
        }

        if ($this->hasStoredGrant($minister, $sacramentKind)) {
            return true;
        }

        $status = $this->legitimacy->currentStatus($minister);
        $grade = $status?->orders_grade ?? ClergyGrade::NONE;
        $allowedGrades = config('church.sacramental_authority.'.$sacramentKind, []);

        if (in_array($grade, $allowedGrades, true)) {
            return true;
        }

        if ($sacramentKind === SacramentKind::ORDERS && $this->hasExtraordinary($minister, 'emergency_orders', $onDate)) {
            return true;
        }

        return false;
    }

    public function grantedKinds(Character $minister): array
    {
        $kinds = [];
        foreach (SacramentKind::all() as $kind) {
            if ($this->canPerform($minister, $kind)) {
                $kinds[] = $kind;
            }
        }

        return $kinds;
    }

    private function hasStoredGrant(Character $minister, string $sacramentKind): bool
    {
        return SacramentalAuthority::query()
            ->where('character_id', $minister->id)
            ->where('sacrament_kind', $sacramentKind)
            ->where('is_current', true)
            ->exists();
    }

    private function hasExtraordinary(Character $minister, string $actionKind, ?CarbonInterface $onDate): bool
    {
        $query = ExtraordinarySpiritualAuthorization::query()
            ->where('character_id', $minister->id)
            ->where('action_kind', $actionKind)
            ->where('is_current', true);

        if ($onDate) {
            $date = $onDate->toDateString();
            $query->where(function ($inner) use ($date) {
                $inner->whereNull('expires_date')->orWhere('expires_date', '>=', $date);
            });
        }

        return $query->exists();
    }
}
