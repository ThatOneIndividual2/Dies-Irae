<?php

namespace App\Domain\Ai\State;

final class Cooldown
{
    public string $actionKey;
    public string $availableOn;

    public function __construct(string $actionKey, string $availableOn)
    {
        $this->actionKey = $actionKey;
        $this->availableOn = $availableOn;
    }

    public function isActive(string $date): bool
    {
        return $date < $this->availableOn;
    }
}
