<?php

namespace App\Domain\Careers;

use App\Domain\Enums\LifeStateKey;

final class LifeStateRules
{
    /**
     * @return array<string,list<string>>
     */
    public static function exclusiveWith(): array
    {
        return [
            LifeStateKey::MARRIED => [LifeStateKey::WIDOWED, LifeStateKey::SOLEMN_VOWS, LifeStateKey::SIMPLE_VOWS],
            LifeStateKey::WIDOWED => [LifeStateKey::MARRIED],
            LifeStateKey::SOLEMN_VOWS => [LifeStateKey::MARRIED, LifeStateKey::SIMPLE_VOWS],
            LifeStateKey::IMPRISONED => [LifeStateKey::EXILE],
            LifeStateKey::EXILE => [LifeStateKey::IMPRISONED],
        ];
    }

    public static function blocksMarriage(array $states): bool
    {
        return in_array(LifeStateKey::SOLEMN_VOWS, $states, true)
            || in_array(LifeStateKey::SIMPLE_VOWS, $states, true)
            || in_array(LifeStateKey::ORDAINED, $states, true)
            || in_array(LifeStateKey::HOLY_ORDER, $states, true);
    }

    public static function blocksSecularInheritance(array $states): bool
    {
        return in_array(LifeStateKey::SOLEMN_VOWS, $states, true)
            || in_array(LifeStateKey::ORDAINED, $states, true)
            || in_array(LifeStateKey::HOLY_ORDER, $states, true);
    }

    public static function blocksCatholicAppointment(array $states): bool
    {
        return in_array(LifeStateKey::HERETIC, $states, true)
            || in_array(LifeStateKey::CULT_MEMBER, $states, true)
            || in_array(LifeStateKey::IMPRISONED, $states, true);
    }

    public static function mayLeaveClergy(array $states, bool $careerAllowsLeave): bool
    {
        if (in_array(LifeStateKey::ORDAINED, $states, true) || in_array(LifeStateKey::SOLEMN_VOWS, $states, true)) {
            return false;
        }

        return $careerAllowsLeave;
    }
}
