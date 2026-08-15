<?php

namespace App\Domain\Campaign\Opening;

use App\Domain\Campaign\CampaignPack;

final class OpeningTriggerCatalog
{
    private array $pack;

    public function __construct(?CampaignPack $pack = null)
    {
        $this->pack = ($pack ?? CampaignPack::europa1347())->get('opening_triggers');
    }

    public function families(): array
    {
        return $this->pack['families'] ?? [];
    }

    public function all(): array
    {
        return $this->pack['triggers'] ?? [];
    }

    public function byKey(string $key): array
    {
        foreach ($this->all() as $trigger) {
            if (($trigger['key'] ?? '') === $key) {
                return $trigger;
            }
        }

        throw new \InvalidArgumentException("Unknown opening trigger: {$key}");
    }
}
