<?php

namespace App\Domain\Time;

final class TimeService implements TimeContract
{
    public function domainKey(): string
    {
        return 'time';
    }
}
