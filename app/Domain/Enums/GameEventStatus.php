<?php

namespace App\Domain\Enums;

final class GameEventStatus
{
    public const SCHEDULED = 'scheduled';
    public const AWAITING_DECISION = 'awaiting_decision';
    public const RESOLVED = 'resolved';
    public const CANCELLED = 'cancelled';
}
