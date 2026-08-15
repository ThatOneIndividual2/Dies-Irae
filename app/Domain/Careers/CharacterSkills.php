<?php

namespace App\Domain\Careers;

use App\Domain\Enums\SkillKey;
use App\Domain\Support\IntClamp;
use InvalidArgumentException;

/**
 * Public, mechanical aptitudes. Interior spiritual state is not stored here.
 */
final class CharacterSkills
{
    public const MIN = 0;
    public const MAX = 20;
    public const DEFAULT = 5;

    /** @var array<string,int> */
    private array $values;

    public function __construct(array $values = [])
    {
        $this->values = [];
        foreach (SkillKey::all() as $key) {
            $this->values[$key] = self::DEFAULT;
        }
        foreach ($values as $key => $value) {
            $this->set($key, (int) $value);
        }
    }

    public function get(string $key): int
    {
        $this->assertSkill($key);

        return $this->values[$key];
    }

    public function set(string $key, int $value): void
    {
        $this->assertSkill($key);
        $this->values[$key] = IntClamp::between($value, self::MIN, self::MAX);
    }

    public function bump(string $key, int $delta): void
    {
        $this->set($key, $this->get($key) + $delta);
    }

    /**
     * @param  array<string,int>  $deltas
     */
    public function bumpMany(array $deltas): void
    {
        foreach ($deltas as $key => $delta) {
            $this->bump($key, $delta);
        }
    }

    public function toArray(): array
    {
        return $this->values;
    }

    public function martial(): int
    {
        return $this->get(SkillKey::MARTIAL);
    }

    public function stewardship(): int
    {
        return $this->get(SkillKey::STEWARDSHIP);
    }

    public function diplomacy(): int
    {
        return $this->get(SkillKey::DIPLOMACY);
    }

    public function intrigue(): int
    {
        return $this->get(SkillKey::INTRIGUE);
    }

    public function learning(): int
    {
        return $this->get(SkillKey::LEARNING);
    }

    public function theology(): int
    {
        return $this->get(SkillKey::THEOLOGY);
    }

    public function medicine(): int
    {
        return $this->get(SkillKey::MEDICINE);
    }

    public function leadership(): int
    {
        return $this->get(SkillKey::LEADERSHIP);
    }

    public function pietyReputation(): int
    {
        return $this->get(SkillKey::PIETY_REPUTATION);
    }

    private function assertSkill(string $key): void
    {
        if (in_array($key, SkillKey::forbiddenAsSkills(), true)) {
            throw new InvalidArgumentException("{$key} is hidden spiritual state, not a career skill");
        }
        if (!in_array($key, SkillKey::all(), true)) {
            throw new InvalidArgumentException("Unknown skill {$key}");
        }
    }
}
