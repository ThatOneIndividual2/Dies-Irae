<?php

namespace App\Domain\Spiritual\Policies;

use App\Domain\Enums\CorruptionSubjectType;
use App\Domain\Spiritual\Effects\ArmyCorruptionPolicy;
use App\Domain\Spiritual\Effects\CharacterCorruptionPolicy;
use App\Domain\Spiritual\Effects\HouseholdCorruptionPolicy;
use App\Domain\Spiritual\Effects\InstitutionCorruptionPolicy;
use App\Domain\Spiritual\Effects\MonasteryCorruptionPolicy;
use App\Domain\Spiritual\Effects\SettlementCorruptionPolicy;
use App\Domain\Spiritual\Effects\TerritoryCorruptionPolicy;
use InvalidArgumentException;

final class CorruptionPolicyRegistry
{
    /** @var array<string, CorruptionEffectPolicy> */
    private array $policies = [];

    public function __construct()
    {
        foreach ([
            new CharacterCorruptionPolicy(),
            new HouseholdCorruptionPolicy(),
            new SettlementCorruptionPolicy(),
            new MonasteryCorruptionPolicy(),
            new ArmyCorruptionPolicy(),
            new TerritoryCorruptionPolicy(),
            new InstitutionCorruptionPolicy(),
        ] as $policy) {
            $this->policies[$policy->subjectType()] = $policy;
        }
    }

    public function for(string $subjectType): CorruptionEffectPolicy
    {
        if (!isset($this->policies[$subjectType])) {
            throw new InvalidArgumentException("No corruption policy for {$subjectType}");
        }

        return $this->policies[$subjectType];
    }

    public function registeredTypes(): array
    {
        return CorruptionSubjectType::all();
    }
}
