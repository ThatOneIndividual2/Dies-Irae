<?php

namespace App\Domain\Ai\State;

use App\Domain\Ai\Enums\Concern;
use App\Domain\Ai\Enums\CrisisKind;

final class Situation
{
    public int $worldId = 1;
    public string $seed = 'dies-irae-ai';
    public string $date = '1348-06-24';
    /** @var array<string, int> concern => 0-100 pressure */
    public array $pressures = [];
    /** @var list<string> */
    public array $crises = [];
    public bool $hasArms = true;
    public bool $hasSacrament = false;
    public bool $isRealmHead = false;
    public bool $isDynastic = true;
    public bool $hasPapalOffice = false;
    public ?string $rivalId = null;
    public ?string $careerPosture = null;
    /** @var list<string>|null */
    public ?array $careerIntents = null;
    public bool $careerAllowsSecularOffice = true;
    public bool $careerAllowsSpiritualOffice = true;

    public function pressure(string $concern): int
    {
        return max(0, min(100, (int) ($this->pressures[$concern] ?? 0)));
    }

    public function inCrisis(string $kind): bool
    {
        return in_array($kind, $this->crises, true);
    }

    public function withPressure(string $concern, int $value): self
    {
        $this->pressures[$concern] = max(0, min(100, $value));

        return $this;
    }

    public function withCrisis(string $kind): self
    {
        if (!in_array($kind, CrisisKind::all(), true)) {
            throw new \InvalidArgumentException("Unknown crisis: {$kind}");
        }
        if (!in_array($kind, $this->crises, true)) {
            $this->crises[] = $kind;
        }

        return $this;
    }

    public static function quiet(): self
    {
        $s = new self();
        foreach (Concern::all() as $c) {
            $s->pressures[$c] = 15;
        }

        return $s;
    }
}
