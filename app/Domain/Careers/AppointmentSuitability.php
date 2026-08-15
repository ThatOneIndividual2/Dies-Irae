<?php

namespace App\Domain\Careers;

use App\Domain\Enums\CareerKey;
use App\Domain\Enums\LifeStateKey;
use App\Domain\Enums\SpiritualOfficeRank;
use App\Domain\Enums\TitleRank;

final class AppointmentSuitability
{
    public bool $eligible;
    public int $score;
    /** @var list<string> */
    public array $reasons;
    public string $kind;
    public string $rank;

    public function __construct(bool $eligible, int $score, array $reasons, string $kind, string $rank)
    {
        $this->eligible = $eligible;
        $this->score = $score;
        $this->reasons = $reasons;
        $this->kind = $kind;
        $this->rank = $rank;
    }

    public static function evaluate(CareerPerson $person, string $kind, string $rank): self
    {
        $reasons = [];
        $score = 0;

        if ($person->hasLifeState(LifeStateKey::IMPRISONED)) {
            return new self(false, 0, ['imprisoned'], $kind, $rank);
        }

        if ($kind === 'spiritual') {
            if (LifeStateRules::blocksCatholicAppointment($person->lifeStates)) {
                $reasons[] = 'canonical_disability';
            }
            if (!$person->hasLifeState(LifeStateKey::ORDAINED) && in_array($rank, [
                SpiritualOfficeRank::PRIEST,
                SpiritualOfficeRank::BISHOP,
                SpiritualOfficeRank::ARCHBISHOP,
                SpiritualOfficeRank::CARDINAL,
                SpiritualOfficeRank::POPE,
                SpiritualOfficeRank::ABBOT,
            ], true)) {
                $reasons[] = 'not_ordained';
            }
            $need = self::clericalNeed($rank);
            if ($need !== null && !self::careerMeets($person, $need)) {
                $reasons[] = 'career_too_low';
            }
            $score += $person->skills->theology() * 3;
            $score += $person->skills->pietyReputation() * 2;
            $score += $person->skills->leadership();
            if ($person->career === CareerKey::INQUISITOR && $rank === SpiritualOfficeRank::BISHOP) {
                $score += 5;
            }
        } else {
            if (LifeStateRules::blocksSecularInheritance($person->lifeStates) && TitleRank::weight($rank) > 0) {
                $reasons[] = 'vows_block_secular_office';
            }
            if ($person->hasLifeState(LifeStateKey::EXILE) && !$person->hasLifeState(LifeStateKey::CLAIMANT)) {
                $reasons[] = 'exile';
            }
            $score += $person->skills->leadership() * 2;
            $score += $person->skills->diplomacy();
            $score += $person->skills->stewardship();
            if ($person->career === CareerKey::LANDED_NOBLE) {
                $score += 8;
            }
            if ($person->career === CareerKey::KNIGHT && TitleRank::weight($rank) <= TitleRank::weight(TitleRank::COUNTY)) {
                $score += 4;
            }
        }

        $eligible = $reasons === [];

        return new self($eligible, $eligible ? $score : 0, $reasons, $kind, $rank);
    }

    /**
     * @param  list<string>  $need
     */
    private static function careerMeets(CareerPerson $person, array $need): bool
    {
        foreach ($need as $career) {
            if ($person->career === $career || in_array($career, $person->careerKeys(), true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return list<string>|null
     */
    private static function clericalNeed(string $rank): ?array
    {
        return match ($rank) {
            SpiritualOfficeRank::BISHOP, SpiritualOfficeRank::ARCHBISHOP => [
                CareerKey::PRIEST, CareerKey::CANON, CareerKey::CHAPLAIN, CareerKey::THEOLOGIAN, CareerKey::ABBOT, CareerKey::BISHOP, CareerKey::ARCHBISHOP,
            ],
            SpiritualOfficeRank::CARDINAL, SpiritualOfficeRank::POPE => [
                CareerKey::BISHOP, CareerKey::ARCHBISHOP, CareerKey::CARDINAL, CareerKey::PAPAL_OFFICIAL,
            ],
            SpiritualOfficeRank::ABBOT => [CareerKey::MONK, CareerKey::ABBOT, CareerKey::PRIEST],
            SpiritualOfficeRank::PRIEST => [CareerKey::PRIEST, CareerKey::NOVICE, CareerKey::MONK],
            default => null,
        };
    }
}
