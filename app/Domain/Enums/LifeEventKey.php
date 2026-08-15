<?php

namespace App\Domain\Enums;

final class LifeEventKey
{
    public const ENTER_CLERGY = 'enter_clergy';
    public const LEAVE_ROLE = 'leave_role';
    public const TAKE_VOWS = 'take_vows';
    public const MARRY = 'marry';
    public const WIDOW = 'widow';
    public const INHERIT = 'inherit';
    public const ORDAIN = 'ordain';
    public const RECEIVE_BENEFICE = 'receive_benefice';
    public const LOSE_OFFICE = 'lose_office';
    public const IMPRISON = 'imprison';
    public const EXILE = 'exile';
    public const JOIN_HOLY_ORDER = 'join_holy_order';
    public const BECOME_HERMIT = 'become_hermit';
    public const BECOME_HERETIC = 'become_heretic';
    public const JOIN_CULT = 'join_cult';
    public const RELEASE = 'release';
    public const RETURN_FROM_EXILE = 'return_from_exile';

    public static function all(): array
    {
        return [
            self::ENTER_CLERGY,
            self::LEAVE_ROLE,
            self::TAKE_VOWS,
            self::MARRY,
            self::WIDOW,
            self::INHERIT,
            self::ORDAIN,
            self::RECEIVE_BENEFICE,
            self::LOSE_OFFICE,
            self::IMPRISON,
            self::EXILE,
            self::JOIN_HOLY_ORDER,
            self::BECOME_HERMIT,
            self::BECOME_HERETIC,
            self::JOIN_CULT,
            self::RELEASE,
            self::RETURN_FROM_EXILE,
        ];
    }
}
