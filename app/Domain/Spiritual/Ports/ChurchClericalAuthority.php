<?php

namespace App\Domain\Spiritual\Ports;

use App\Domain\Church\ClergyLegitimacy;
use App\Domain\Church\SacramentalAuthorityResolver;
use App\Domain\Enums\ClergyGrade;
use App\Domain\Enums\HolyOrdersGrade;
use App\Domain\Enums\SacramentKind;
use App\Domain\Enums\SacramentType;
use App\Models\Character;
use App\Models\ExorcistLegitimacy;

/**
 * Production Church adapter. Tests bind InMemoryClericalAuthority instead.
 */
final class ChurchClericalAuthority implements ClericalAuthorityPort
{
    public function __construct(
        private ClergyLegitimacy $legitimacy,
        private SacramentalAuthorityResolver $authority
    ) {
    }

    public function isCleric(Character $character): bool
    {
        return $this->holyOrdersGrade($character) !== HolyOrdersGrade::NONE;
    }

    public function holyOrdersGrade(Character $character): string
    {
        $status = $this->legitimacy->currentStatus($character);
        if ($status === null) {
            return HolyOrdersGrade::NONE;
        }

        $raw = $status->orders_grade ?? $status->status ?? HolyOrdersGrade::NONE;

        return $this->mapGrade((string) $raw);
    }

    public function mayMinisterSacrament(Character $minister, string $sacramentType): bool
    {
        if ($this->legitimacy->isExcommunicated($minister)) {
            return false;
        }

        return $this->authority->canPerform($minister, $this->toChurchKind($sacramentType));
    }

    public function mayHearConfession(Character $minister, Character $penitent): bool
    {
        return $this->mayMinisterSacrament($minister, SacramentType::CONFESSION)
            && $this->hasJurisdiction($minister, $penitent);
    }

    public function hasJurisdiction(Character $cleric, Character $subject): bool
    {
        if (!$this->isCleric($cleric) || $this->legitimacy->isExcommunicated($cleric)) {
            return false;
        }

        return (int) $cleric->world_id === (int) $subject->world_id;
    }

    public function isLicensedExorcist(Character $cleric): bool
    {
        return ExorcistLegitimacy::query()
            ->where('character_id', $cleric->id)
            ->where('is_current', true)
            ->exists();
    }

    private function mapGrade(string $grade): string
    {
        return match ($grade) {
            ClergyGrade::BISHOP, HolyOrdersGrade::BISHOP => HolyOrdersGrade::BISHOP,
            ClergyGrade::PRIEST, HolyOrdersGrade::PRIEST => HolyOrdersGrade::PRIEST,
            ClergyGrade::DEACON, HolyOrdersGrade::DEACON => HolyOrdersGrade::DEACON,
            ClergyGrade::TONSURE,
            ClergyGrade::PORTER,
            ClergyGrade::LECTOR,
            ClergyGrade::ACOLYTE,
            ClergyGrade::SUBDEACON,
            HolyOrdersGrade::MINOR => HolyOrdersGrade::MINOR,
            default => HolyOrdersGrade::NONE,
        };
    }

    private function toChurchKind(string $sacramentType): string
    {
        return match ($sacramentType) {
            SacramentType::CONFESSION => SacramentKind::PENANCE,
            SacramentType::ANOINTING => SacramentKind::UNCTION,
            SacramentType::HOLY_ORDERS => SacramentKind::ORDERS,
            default => $sacramentType,
        };
    }
}
