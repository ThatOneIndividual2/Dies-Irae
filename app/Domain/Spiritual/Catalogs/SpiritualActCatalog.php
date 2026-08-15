<?php

namespace App\Domain\Spiritual\Catalogs;

use App\Domain\Enums\SpiritualActType;
use InvalidArgumentException;

final class SpiritualActCatalog
{
    private array $effects;

    public function __construct(?array $effects = null)
    {
        $this->effects = $effects ?? config('spiritual.act_effects', []);
    }

    public function definition(string $actType): array
    {
        if (!isset($this->effects[$actType])) {
            throw new InvalidArgumentException("Unknown spiritual act: {$actType}");
        }

        return $this->effects[$actType];
    }

    public function deltas(string $actType): array
    {
        return $this->definition($actType)['deltas'] ?? [];
    }

    /**
     * Signature used to prove acts are multi-axis, not a single meter.
     *
     * @return array<string,int>
     */
    public function signature(string $actType): array
    {
        $deltas = $this->deltas($actType);
        ksort($deltas);

        return $deltas;
    }

    public function knownActs(): array
    {
        return SpiritualActType::all();
    }
}
