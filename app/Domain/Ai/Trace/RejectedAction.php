<?php

namespace App\Domain\Ai\Trace;

final class RejectedAction
{
    public string $key;
    public string $reason;

    public function __construct(string $key, string $reason)
    {
        $this->key = $key;
        $this->reason = $reason;
    }

    public function toArray(): array
    {
        return ['key' => $this->key, 'reason' => $this->reason];
    }
}
