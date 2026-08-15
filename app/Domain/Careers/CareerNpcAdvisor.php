<?php

namespace App\Domain\Careers;

use App\Domain\Ai\State\Situation;

final class CareerNpcAdvisor
{
    public function __construct(private CareerEngine $engine)
    {
    }

    public function profile(CareerPerson $person): NpcBehaviorProfile
    {
        return $this->engine->behavior($person);
    }

    public function behavior(CareerPerson $person): NpcBehaviorProfile
    {
        return $this->profile($person);
    }

    public function wouldAcceptAppointment(CareerPerson $person, string $kind, string $rank): bool
    {
        $profile = $this->profile($person);
        if ($kind === 'spiritual') {
            return $profile->willAcceptSpiritualOffice
                && $this->engine->suitability($person, $kind, $rank)->eligible;
        }

        return $profile->willAcceptSecularOffice
            && $this->engine->suitability($person, $kind, $rank)->eligible;
    }

    public function applyToSituation(Situation $situation, CareerPerson $person): Situation
    {
        $profile = $this->profile($person);
        $situation->careerPosture = $profile->posture;
        $situation->careerIntents = $profile->intents;
        $situation->careerAllowsSecularOffice = $profile->willAcceptSecularOffice;
        $situation->careerAllowsSpiritualOffice = $profile->willAcceptSpiritualOffice;

        return $situation;
    }
}
