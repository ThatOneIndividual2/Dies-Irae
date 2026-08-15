<?php

namespace App\Domain\NPC;

use App\Domain\Ai\StrategicAiEngine;
use App\Domain\Ai\State\AiActor;
use App\Domain\Ai\State\Situation;
use App\Domain\Ai\Trace\Decision;
use App\Domain\Ai\Trace\DecisionTrace;
use App\Domain\Careers\CareerPerson;
use App\Domain\Careers\NpcBehaviorProfile;

/**
 * Domain contract for NPC. Bound by NPCServiceProvider.
 * Implementations must not import or query Feudalism.
 * There is no universal NPC brain. StrategicAiEngine dispatches to typed reasoners.
 */
interface NPCContract
{
    public function domainKey(): string;

    public function engine(): StrategicAiEngine;

    public function consider(AiActor $actor, Situation $situation): Decision;

    public function considerWithCareer(AiActor $actor, Situation $situation, CareerPerson $person): Decision;

    public function explain(Decision $decision): DecisionTrace;

    public function behavior(CareerPerson $person): NpcBehaviorProfile;

    public function wouldAcceptAppointment(CareerPerson $person, string $kind, string $rank): bool;
}
