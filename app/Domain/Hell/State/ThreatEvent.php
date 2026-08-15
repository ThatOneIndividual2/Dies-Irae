<?php

namespace App\Domain\Hell\State;

final class ThreatEvent
{
    public string $type;
    public string $territoryId;
    public string $message;
    /** @var array<string, mixed> */
    public array $payload = [];

    public function __construct(string $type, string $territoryId, string $message, array $payload = [])
    {
        $this->type = $type;
        $this->territoryId = $territoryId;
        $this->message = $message;
        $this->payload = $payload;
    }

    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'territoryId' => $this->territoryId,
            'message' => $this->message,
            'payload' => $this->payload,
        ];
    }
}
