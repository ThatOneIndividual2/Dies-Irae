<?php

namespace App\Domain\Careers;

use App\Domain\Enums\CareerKey;
use App\Domain\Enums\EducationSource;
use App\Domain\Enums\SkillKey;

final class EducationCatalog
{
    /**
     * @return array<string,array<string,mixed>>
     */
    public static function all(): array
    {
        return [
            EducationSource::NOBLE_HOUSEHOLD => [
                'skills' => [SkillKey::DIPLOMACY => 2, SkillKey::LEADERSHIP => 2, SkillKey::MARTIAL => 1],
                'opens' => [CareerKey::PAGE, CareerKey::COURTIER],
            ],
            EducationSource::MILITARY_HOUSEHOLD => [
                'skills' => [SkillKey::MARTIAL => 3, SkillKey::LEADERSHIP => 2],
                'opens' => [CareerKey::PAGE, CareerKey::SQUIRE],
            ],
            EducationSource::MONASTERY_SCHOOL => [
                'skills' => [SkillKey::THEOLOGY => 3, SkillKey::LEARNING => 2, SkillKey::PIETY_REPUTATION => 2],
                'opens' => [CareerKey::NOVICE, CareerKey::SCHOLAR],
            ],
            EducationSource::CATHEDRAL_SCHOOL => [
                'skills' => [SkillKey::THEOLOGY => 2, SkillKey::LEARNING => 3, SkillKey::DIPLOMACY => 1],
                'opens' => [CareerKey::NOVICE, CareerKey::SCHOLAR, CareerKey::PRIEST],
            ],
            EducationSource::UNIVERSITY => [
                'skills' => [SkillKey::LEARNING => 4, SkillKey::THEOLOGY => 2, SkillKey::MEDICINE => 1],
                'opens' => [CareerKey::SCHOLAR, CareerKey::PHYSICIAN, CareerKey::THEOLOGIAN, CareerKey::MAGISTRATE],
            ],
            EducationSource::APPRENTICESHIP => [
                'skills' => [SkillKey::STEWARDSHIP => 3, SkillKey::MEDICINE => 2],
                'opens' => [CareerKey::MERCHANT, CareerKey::PHYSICIAN],
            ],
        ];
    }

    public static function get(string $source): array
    {
        $all = self::all();
        if (!isset($all[$source])) {
            throw new CareerException("Unknown education source {$source}");
        }

        return $all[$source];
    }
}
