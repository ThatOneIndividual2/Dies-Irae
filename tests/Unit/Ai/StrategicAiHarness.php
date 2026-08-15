<?php

namespace Tests\Unit\Ai;

use App\Domain\Ai\Enums\Concern;
use App\Domain\Ai\Enums\CrisisKind;
use App\Domain\Ai\State\AiActor;
use App\Domain\Ai\State\Personality;
use App\Domain\Ai\State\Situation;
use App\Domain\Ai\StrategicAiEngine;

final class StrategicAiHarness
{
    public static function dataDir(): string
    {
        return dirname(__DIR__, 3).'/database/data/ai';
    }

    public static function engine(): StrategicAiEngine
    {
        return StrategicAiEngine::fromDataDirectory(self::dataDir());
    }

    public static function actor(string $id, string $type, array $personality = []): AiActor
    {
        $actor = new AiActor($id, $type);
        $actor->personality = Personality::fromArray($personality);
        $actor->riskTolerance = self::engine()->profiles()->riskTolerance($type);

        return $actor;
    }

    public static function incursion(): Situation
    {
        $s = Situation::quiet();
        $s->withPressure(Concern::INFERNAL_THREAT, 85)
            ->withPressure(Concern::TERRITORIAL_SECURITY, 62)
            ->withPressure(Concern::FAITH, 55)
            ->withPressure(Concern::CHURCH_STANDING, 40)
            ->withPressure(Concern::FEAR, 50)
            ->withPressure(Concern::AMBITION, 20)
            ->withCrisis(CrisisKind::DEMONIC_INCURSION);
        $s->hasArms = true;
        $s->hasSacrament = true;
        $s->isRealmHead = true;
        $s->isDynastic = true;
        $s->hasPapalOffice = true;

        return $s;
    }

    public static function plague(): Situation
    {
        $s = Situation::quiet();
        $s->withPressure(Concern::PLAGUE_SAFETY, 88)
            ->withPressure(Concern::SURVIVAL, 60)
            ->withPressure(Concern::WEALTH, 45)
            ->withPressure(Concern::AMBITION, 20)
            ->withCrisis(CrisisKind::PLAGUE);
        $s->hasArms = true;

        return $s;
    }

    public static function famine(): Situation
    {
        $s = Situation::quiet();
        $s->withPressure(Concern::FAMINE, 85)
            ->withPressure(Concern::LOYALTY, 40)
            ->withPressure(Concern::WEALTH, 35)
            ->withCrisis(CrisisKind::FAMINE);

        return $s;
    }

    public static function succession(): Situation
    {
        $s = Situation::quiet();
        $s->withPressure(Concern::DYNASTY, 80)
            ->withPressure(Concern::LEGITIMACY, 75)
            ->withPressure(Concern::AMBITION, 70)
            ->withPressure(Concern::TERRITORIAL_SECURITY, 35)
            ->withCrisis(CrisisKind::SUCCESSION);
        $s->isDynastic = true;
        $s->isRealmHead = false;

        return $s;
    }

    public static function cultDiscovery(): Situation
    {
        $s = Situation::quiet();
        $s->withPressure(Concern::FAITH, 60)
            ->withPressure(Concern::CORRUPTION, 55)
            ->withPressure(Concern::INFERNAL_THREAT, 40)
            ->withPressure(Concern::SURVIVAL, 50)
            ->withCrisis(CrisisKind::CULT_DISCOVERY);
        $s->hasSacrament = true;

        return $s;
    }

    public static function heresy(): Situation
    {
        $s = Situation::quiet();
        $s->withPressure(Concern::FAITH, 70)
            ->withPressure(Concern::CHURCH_STANDING, 50)
            ->withPressure(Concern::LEGITIMACY, 40)
            ->withCrisis(CrisisKind::HERESY);
        $s->hasSacrament = true;

        return $s;
    }
}
