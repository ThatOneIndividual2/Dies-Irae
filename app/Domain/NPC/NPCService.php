<?php

namespace App\Domain\NPC;

use App\Domain\Ai\StrategicAiEngine;
use App\Domain\Ai\State\AiActor;
use App\Domain\Ai\State\Situation;
use App\Domain\Ai\Trace\Decision;
use App\Domain\Ai\Trace\DecisionTrace;
use App\Domain\Careers\CareerEngine;
use App\Domain\Careers\CareerNpcAdvisor;
use App\Domain\Careers\CareerPerson;
use App\Domain\Careers\NpcBehaviorProfile;

final class NPCService implements NPCContract
{
    private CareerNpcAdvisor $careers;

    public function __construct(private StrategicAiEngine $engine, ?CareerEngine $careerEngine = null)
    {
        $this->careers = new CareerNpcAdvisor($careerEngine ?? new CareerEngine());
    }

    public function domainKey(): string
    {
        return 'npc';
    }

    public function engine(): StrategicAiEngine
    {
        return $this->engine;
    }

    public function consider(AiActor $actor, Situation $situation): Decision
    {
        return $this->engine->consider($actor, $situation);
    }

    public function considerWithCareer(AiActor $actor, Situation $situation, CareerPerson $person): Decision
    {
        $this->careers->applyToSituation($situation, $person);

        return $this->engine->consider($actor, $situation);
    }

    public function explain(Decision $decision): DecisionTrace
    {
        return $decision->trace;
    }

    public function behavior(CareerPerson $person): NpcBehaviorProfile
    {
        return $this->careers->behavior($person);
    }

    public function wouldAcceptAppointment(CareerPerson $person, string $kind, string $rank): bool
    {
        return $this->careers->wouldAcceptAppointment($person, $kind, $rank);
    }
}
