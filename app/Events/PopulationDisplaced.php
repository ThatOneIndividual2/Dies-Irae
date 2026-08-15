<?php

namespace App\Events;

final class PopulationDisplaced
{
    public int $worldId;
    public string $fromId;
    public ?string $toId;
    public int $souls;
    public string $cause;

    public function __construct(int $worldId, string $fromId, ?string $toId, int $souls, string $cause)
    {
        $this->worldId = $worldId;
        $this->fromId = $fromId;
        $this->toId = $toId;
        $this->souls = $souls;
        $this->cause = $cause;
    }
}
