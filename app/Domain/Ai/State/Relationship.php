<?php

namespace App\Domain\Ai\State;

final class Relationship
{
    public string $targetId;
    public int $standing = 0;
    public string $kind = 'acquaintance';

    public function __construct(string $targetId, int $standing = 0, string $kind = 'acquaintance')
    {
        $this->targetId = $targetId;
        $this->standing = max(-100, min(100, $standing));
        $this->kind = $kind;
    }
}
