<?php

namespace App\Domain\Ai\Catalog;

use App\Domain\Ai\Enums\ActorType;
use App\Domain\Ai\Enums\Concern;
use InvalidArgumentException;

final class ProfileCatalog
{
    /** @var array<string, array> */
    private array $profiles = [];

    /** @var array<string, array<string, float>> */
    private array $crises = [];

    public static function load(string $profilePath, string $crisisPath): self
    {
        $profiles = json_decode((string) file_get_contents($profilePath), true);
        $crises = json_decode((string) file_get_contents($crisisPath), true);
        if (!is_array($profiles) || !is_array($crises)) {
            throw new InvalidArgumentException('AI profile or crisis catalog is invalid JSON');
        }

        $c = new self();
        $c->profiles = $profiles;
        $c->crises = $crises;
        foreach (ActorType::all() as $type) {
            if (!isset($c->profiles[$type])) {
                throw new InvalidArgumentException("AI profile missing for {$type}");
            }
        }

        return $c;
    }

    public function profile(string $type): array
    {
        if (!isset($this->profiles[$type])) {
            throw new InvalidArgumentException("Unknown AI profile: {$type}");
        }

        return $this->profiles[$type];
    }

    /** @return array<string, float> */
    public function concernWeights(string $type): array
    {
        $weights = $this->profile($type)['concern_weights'] ?? [];
        $out = [];
        foreach (Concern::all() as $concern) {
            $out[$concern] = (float) ($weights[$concern] ?? 0.3);
        }

        return $out;
    }

    /** @return list<string> */
    public function actions(string $type): array
    {
        return array_values($this->profile($type)['actions'] ?? ['wait']);
    }

    /** @return list<string> */
    public function forbidden(string $type): array
    {
        return array_values($this->profile($type)['forbidden_actions'] ?? []);
    }

    public function riskTolerance(string $type): float
    {
        return (float) ($this->profile($type)['risk_tolerance'] ?? 0.4);
    }

    /** @return array<string, float> */
    public function crisisShift(string $kind): array
    {
        return $this->crises[$kind] ?? [];
    }

    public function grain(string $type): string
    {
        return (string) ($this->profile($type)['grain'] ?? ActorType::grain($type));
    }
}
