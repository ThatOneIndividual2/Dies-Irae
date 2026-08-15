<?php

namespace App\Domain\Careers;

use App\Domain\Enums\CareerKey;
use App\Domain\Enums\EducationSource;
use App\Domain\Enums\NpcIntent;
use App\Domain\Enums\SkillKey;

final class CareerCatalog
{
    /**
     * @return array<string,array<string,mixed>>
     */
    public static function all(): array
    {
        return [
            CareerKey::PAGE => self::def(CareerKey::PAGE, 'secular', 'arms', 1, 7, 16, [CareerKey::SQUIRE], [SkillKey::MARTIAL, SkillKey::LEADERSHIP], [NpcIntent::TRAIN, NpcIntent::SERVE_HOUSEHOLD], [], true, [EducationSource::NOBLE_HOUSEHOLD, EducationSource::MILITARY_HOUSEHOLD]),
            CareerKey::SQUIRE => self::def(CareerKey::SQUIRE, 'secular', 'arms', 2, 14, 22, [CareerKey::KNIGHT], [SkillKey::MARTIAL, SkillKey::LEADERSHIP], [NpcIntent::TRAIN, NpcIntent::SEEK_KNIGHTHOOD], [], false, [EducationSource::MILITARY_HOUSEHOLD, EducationSource::NOBLE_HOUSEHOLD]),
            CareerKey::KNIGHT => self::def(CareerKey::KNIGHT, 'secular', 'arms', 3, 18, 80, [CareerKey::LANDED_NOBLE, CareerKey::MERCENARY_CAPTAIN, CareerKey::MARSHAL, CareerKey::HOUSEHOLD_OFFICER], [SkillKey::MARTIAL, SkillKey::LEADERSHIP], [NpcIntent::COMMAND, NpcIntent::SEEK_TITLE], ['knight'], false, []),
            CareerKey::MERCENARY_CAPTAIN => self::def(CareerKey::MERCENARY_CAPTAIN, 'secular', 'arms', 4, 20, 80, [CareerKey::KNIGHT], [SkillKey::MARTIAL, SkillKey::LEADERSHIP], [NpcIntent::RAID, NpcIntent::COMMAND], ['captain'], true, []),
            CareerKey::COURTIER => self::def(CareerKey::COURTIER, 'secular', 'court', 1, 12, 80, [CareerKey::DIPLOMAT, CareerKey::MAGISTRATE, CareerKey::STEWARD, CareerKey::MARSHAL, CareerKey::HOUSEHOLD_OFFICER, CareerKey::LANDED_NOBLE], [SkillKey::DIPLOMACY, SkillKey::INTRIGUE], [NpcIntent::SERVE_HOUSEHOLD, NpcIntent::NEGOTIATE], ['court'], true, [EducationSource::NOBLE_HOUSEHOLD]),
            CareerKey::HOUSEHOLD_OFFICER => self::def(CareerKey::HOUSEHOLD_OFFICER, 'secular', 'court', 2, 16, 80, [CareerKey::STEWARD, CareerKey::MARSHAL, CareerKey::LANDED_NOBLE], [SkillKey::STEWARDSHIP, SkillKey::LEADERSHIP], [NpcIntent::ADMINISTER, NpcIntent::SERVE_HOUSEHOLD], ['officer'], true, []),
            CareerKey::STEWARD => self::def(CareerKey::STEWARD, 'secular', 'court', 3, 18, 80, [CareerKey::LANDED_NOBLE], [SkillKey::STEWARDSHIP], [NpcIntent::ADMINISTER], ['steward'], true, []),
            CareerKey::MARSHAL => self::def(CareerKey::MARSHAL, 'secular', 'court', 3, 20, 80, [CareerKey::LANDED_NOBLE], [SkillKey::MARTIAL, SkillKey::LEADERSHIP], [NpcIntent::COMMAND], ['marshal'], true, []),
            CareerKey::DIPLOMAT => self::def(CareerKey::DIPLOMAT, 'secular', 'court', 3, 18, 80, [CareerKey::LANDED_NOBLE], [SkillKey::DIPLOMACY], [NpcIntent::NEGOTIATE], ['diplomat'], true, []),
            CareerKey::MAGISTRATE => self::def(CareerKey::MAGISTRATE, 'secular', 'court', 3, 22, 80, [CareerKey::LANDED_NOBLE], [SkillKey::LEARNING, SkillKey::STEWARDSHIP], [NpcIntent::JUDGE], ['magistrate'], true, [EducationSource::UNIVERSITY]),
            CareerKey::LANDED_NOBLE => self::def(CareerKey::LANDED_NOBLE, 'secular', 'court', 5, 16, 80, [], [SkillKey::LEADERSHIP, SkillKey::STEWARDSHIP, SkillKey::DIPLOMACY], [NpcIntent::SEEK_TITLE, NpcIntent::ADMINISTER, NpcIntent::COMMAND], ['landed'], false, [EducationSource::NOBLE_HOUSEHOLD]),
            CareerKey::SCHOLAR => self::def(CareerKey::SCHOLAR, 'secular', 'civic', 2, 14, 80, [CareerKey::THEOLOGIAN, CareerKey::PHYSICIAN, CareerKey::MAGISTRATE], [SkillKey::LEARNING], [NpcIntent::STUDY], ['scholar'], true, [EducationSource::UNIVERSITY, EducationSource::CATHEDRAL_SCHOOL, EducationSource::MONASTERY_SCHOOL]),
            CareerKey::PHYSICIAN => self::def(CareerKey::PHYSICIAN, 'secular', 'civic', 3, 20, 80, [], [SkillKey::MEDICINE, SkillKey::LEARNING], [NpcIntent::HEAL], ['physician'], true, [EducationSource::UNIVERSITY, EducationSource::APPRENTICESHIP]),
            CareerKey::MERCHANT => self::def(CareerKey::MERCHANT, 'secular', 'civic', 2, 14, 80, [], [SkillKey::STEWARDSHIP, SkillKey::DIPLOMACY], [NpcIntent::TRADE], ['merchant'], true, [EducationSource::APPRENTICESHIP]),

            CareerKey::NOVICE => self::def(CareerKey::NOVICE, 'clerical', 'monastic', 1, 10, 25, [CareerKey::MONK, CareerKey::PRIEST], [SkillKey::THEOLOGY, SkillKey::PIETY_REPUTATION], [NpcIntent::PRAY, NpcIntent::TRAIN], ['novice'], true, [EducationSource::MONASTERY_SCHOOL, EducationSource::CATHEDRAL_SCHOOL]),
            CareerKey::MONK => self::def(CareerKey::MONK, 'clerical', 'monastic', 2, 16, 80, [CareerKey::ABBOT, CareerKey::PRIEST, CareerKey::THEOLOGIAN], [SkillKey::THEOLOGY, SkillKey::PIETY_REPUTATION], [NpcIntent::PRAY, NpcIntent::STUDY], ['monk'], false, [EducationSource::MONASTERY_SCHOOL]),
            CareerKey::PRIEST => self::def(CareerKey::PRIEST, 'clerical', 'secular_clergy', 3, 24, 80, [CareerKey::CHAPLAIN, CareerKey::CANON, CareerKey::THEOLOGIAN, CareerKey::INQUISITOR, CareerKey::EXORCIST, CareerKey::BISHOP, CareerKey::PAPAL_OFFICIAL, CareerKey::ABBOT], [SkillKey::THEOLOGY, SkillKey::PIETY_REPUTATION], [NpcIntent::PREACH, NpcIntent::SEEK_BENEFICE], ['priest'], false, [EducationSource::CATHEDRAL_SCHOOL, EducationSource::UNIVERSITY]),
            CareerKey::ABBOT => self::def(CareerKey::ABBOT, 'clerical', 'monastic', 4, 28, 80, [CareerKey::BISHOP], [SkillKey::STEWARDSHIP, SkillKey::THEOLOGY, SkillKey::LEADERSHIP], [NpcIntent::ADMINISTER, NpcIntent::PRAY], ['abbot'], false, []),
            CareerKey::CANON => self::def(CareerKey::CANON, 'clerical', 'secular_clergy', 4, 26, 80, [CareerKey::BISHOP, CareerKey::PAPAL_OFFICIAL, CareerKey::THEOLOGIAN], [SkillKey::THEOLOGY, SkillKey::LEARNING], [NpcIntent::STUDY, NpcIntent::SEEK_BENEFICE], ['canon'], false, []),
            CareerKey::CHAPLAIN => self::def(CareerKey::CHAPLAIN, 'clerical', 'secular_clergy', 4, 24, 80, [CareerKey::BISHOP, CareerKey::CANON], [SkillKey::THEOLOGY, SkillKey::DIPLOMACY], [NpcIntent::PREACH, NpcIntent::SERVE_HOUSEHOLD], ['chaplain'], false, []),
            CareerKey::THEOLOGIAN => self::def(CareerKey::THEOLOGIAN, 'clerical', 'secular_clergy', 4, 26, 80, [CareerKey::BISHOP, CareerKey::PAPAL_OFFICIAL, CareerKey::INQUISITOR], [SkillKey::THEOLOGY, SkillKey::LEARNING], [NpcIntent::STUDY, NpcIntent::PREACH], ['theologian'], false, [EducationSource::UNIVERSITY]),
            CareerKey::INQUISITOR => self::def(CareerKey::INQUISITOR, 'clerical', 'secular_clergy', 5, 28, 80, [CareerKey::BISHOP], [SkillKey::THEOLOGY, SkillKey::INTRIGUE], [NpcIntent::HUNT_HERESY], ['inquisitor'], false, []),
            CareerKey::EXORCIST => self::def(CareerKey::EXORCIST, 'clerical', 'secular_clergy', 5, 26, 80, [CareerKey::PRIEST], [SkillKey::THEOLOGY, SkillKey::PIETY_REPUTATION], [NpcIntent::EXORCISE], ['exorcist'], false, []),
            CareerKey::BISHOP => self::def(CareerKey::BISHOP, 'clerical', 'episcopal', 6, 30, 80, [CareerKey::ARCHBISHOP, CareerKey::CARDINAL], [SkillKey::THEOLOGY, SkillKey::LEADERSHIP, SkillKey::PIETY_REPUTATION], [NpcIntent::GOVERN_SEE, NpcIntent::PREACH], ['bishop'], false, []),
            CareerKey::ARCHBISHOP => self::def(CareerKey::ARCHBISHOP, 'clerical', 'episcopal', 7, 35, 80, [CareerKey::CARDINAL], [SkillKey::THEOLOGY, SkillKey::LEADERSHIP, SkillKey::DIPLOMACY], [NpcIntent::GOVERN_SEE], ['archbishop'], false, []),
            CareerKey::CARDINAL => self::def(CareerKey::CARDINAL, 'clerical', 'episcopal', 8, 35, 80, [CareerKey::PAPAL_OFFICIAL], [SkillKey::THEOLOGY, SkillKey::INTRIGUE, SkillKey::DIPLOMACY], [NpcIntent::GOVERN_SEE, NpcIntent::NEGOTIATE], ['cardinal'], false, []),
            CareerKey::PAPAL_OFFICIAL => self::def(CareerKey::PAPAL_OFFICIAL, 'clerical', 'curia', 5, 28, 80, [CareerKey::CARDINAL, CareerKey::BISHOP], [SkillKey::INTRIGUE, SkillKey::DIPLOMACY, SkillKey::THEOLOGY], [NpcIntent::NEGOTIATE, NpcIntent::ADMINISTER], ['curia'], false, []),
        ];
    }

    public static function get(string $key): array
    {
        $all = self::all();
        if ($key === CareerKey::NONE) {
            return self::def(CareerKey::NONE, 'none', 'none', 0, 0, 120, CareerKey::secular(), [], [], [], true, []);
        }
        if (!isset($all[$key])) {
            throw new CareerException("Unknown career {$key}");
        }

        return $all[$key];
    }

    public static function mayAdvance(string $from, string $to): bool
    {
        if ($from === $to) {
            return true;
        }
        if ($from === CareerKey::NONE) {
            return self::get($to)['entry'];
        }
        $def = self::get($from);

        return in_array($to, $def['next'], true);
    }

    /**
     * @param  array<int,string>  $educations
     */
    public static function educationAllows(string $career, array $educations): bool
    {
        $required = self::get($career)['educations'];
        if ($required === []) {
            return true;
        }
        foreach ($required as $source) {
            if (in_array($source, $educations, true)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string,mixed>
     */
    private static function def(
        string $key,
        string $domain,
        string $ladder,
        int $tier,
        int $minAge,
        int $maxAge,
        array $next,
        array $skills,
        array $intents,
        array $appointmentTags,
        bool $mayLeave,
        array $educations
    ): array {
        return [
            'key' => $key,
            'domain' => $domain,
            'ladder' => $ladder,
            'tier' => $tier,
            'min_age' => $minAge,
            'max_age' => $maxAge,
            'next' => $next,
            'skills' => $skills,
            'intents' => $intents,
            'appointment_tags' => $appointmentTags,
            'may_leave' => $mayLeave,
            'educations' => $educations,
            'entry' => $mayLeave,
        ];
    }
}
