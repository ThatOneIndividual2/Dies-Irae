<?php

namespace App\Domain\Spiritual\Policies;

use App\Domain\Enums\CanonicalCensure;
use App\Domain\Enums\EucharistStanding;
use App\Domain\Enums\HolyOrdersGrade;
use App\Domain\Enums\SacramentType;
use App\Domain\Enums\SacramentValidity;
use App\Domain\Spiritual\Ports\ClericalAuthorityPort;
use App\Models\Character;
use App\Models\CharacterCanonicalState;
use App\Models\SacramentRecord;

final class SacramentEligibilityPolicy
{
    public function __construct(private ClericalAuthorityPort $clergy)
    {
    }

    /**
     * @return array{allowed:bool, validity:string, reasons:string[]}
     */
    public function evaluate(
        string $sacramentType,
        Character $subject,
        CharacterCanonicalState $canonical,
        ?Character $minister = null,
        ?Character $spouse = null
    ): array {
        $reasons = [];

        if ($sacramentType !== SacramentType::BAPTISM && !$canonical->is_baptized) {
            $reasons[] = 'unbaptized';
        }

        if ($minister === null) {
            if (!in_array($sacramentType, [SacramentType::BAPTISM], true)) {
                $reasons[] = 'minister_required';
            }
        } elseif (!$this->clergy->mayMinisterSacrament($minister, $sacramentType)) {
            $reasons[] = 'minister_lacks_faculty';
        }

        if (CanonicalCensure::barsCommunion($canonical->censure) && in_array($sacramentType, [
            SacramentType::EUCHARIST,
            SacramentType::CONFIRMATION,
            SacramentType::MATRIMONY,
            SacramentType::HOLY_ORDERS,
        ], true)) {
            $reasons[] = 'under_censure';
        }

        if ($sacramentType === SacramentType::EUCHARIST && !EucharistStanding::mayReceive($canonical->eucharist_standing)) {
            $reasons[] = 'not_in_communion';
        }

        if ($sacramentType === SacramentType::CONFESSION) {
            if ($minister === null || !$this->clergy->mayHearConfession($minister, $subject)) {
                $reasons[] = 'no_confession_faculty';
            }
        }

        if ($sacramentType === SacramentType::CONFIRMATION && $canonical->is_confirmed) {
            $reasons[] = 'already_confirmed';
        }

        if ($sacramentType === SacramentType::BAPTISM && $canonical->is_baptized) {
            $reasons[] = 'already_baptized';
        }

        if ($sacramentType === SacramentType::MATRIMONY) {
            if ($canonical->matrimonial_bond_character_id) {
                $reasons[] = 'existing_bond';
            }
            if ($canonical->holy_orders_grade !== HolyOrdersGrade::NONE
                && HolyOrdersGrade::weight($canonical->holy_orders_grade) >= HolyOrdersGrade::weight(HolyOrdersGrade::DEACON)) {
                $reasons[] = 'orders_impediment';
            }
            if ($spouse === null) {
                $reasons[] = 'spouse_required';
            } else {
                $spouseCanonical = CharacterCanonicalState::query()
                    ->where('character_id', $spouse->id)
                    ->where('is_current', true)
                    ->first();
                if ($spouseCanonical && $spouseCanonical->matrimonial_bond_character_id) {
                    $reasons[] = 'spouse_existing_bond';
                }
                if (!$spouseCanonical || !$spouseCanonical->is_baptized) {
                    $reasons[] = 'spouse_unbaptized';
                }
            }
        }

        if ($sacramentType === SacramentType::HOLY_ORDERS && $canonical->matrimonial_bond_character_id) {
            $reasons[] = 'marriage_impediment';
        }

        $blocking = array_values(array_filter($reasons, function ($reason) {
            return !in_array($reason, ['minister_lacks_faculty'], true);
        }));

        $illicitOnly = $reasons === ['minister_lacks_faculty'];

        if ($illicitOnly) {
            return [
                'allowed' => true,
                'validity' => SacramentValidity::VALID_ILLICIT,
                'reasons' => $reasons,
            ];
        }

        if ($blocking !== []) {
            return [
                'allowed' => false,
                'validity' => SacramentValidity::INVALID,
                'reasons' => $reasons,
            ];
        }

        return [
            'allowed' => true,
            'validity' => SacramentValidity::VALID,
            'reasons' => [],
        ];
    }

    public function hasValidRecord(Character $subject, string $sacramentType): bool
    {
        return SacramentRecord::query()
            ->where('subject_character_id', $subject->id)
            ->where('sacrament_type', $sacramentType)
            ->whereIn('validity', [SacramentValidity::VALID, SacramentValidity::VALID_ILLICIT])
            ->exists();
    }
}
