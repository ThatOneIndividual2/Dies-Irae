<?php

namespace App\Domain\Enums;

final class PenanceStatus
{
    public const ASSIGNED = 'assigned';
    public const IN_PROGRESS = 'in_progress';
    public const COMPLETED = 'completed';
    public const NEGLECTED = 'neglected';

    public static function all(): array
    {
        return [self::ASSIGNED, self::IN_PROGRESS, self::COMPLETED, self::NEGLECTED];
    }
}
