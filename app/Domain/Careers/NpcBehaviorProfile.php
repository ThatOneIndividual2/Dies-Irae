<?php

namespace App\Domain\Careers;

use App\Domain\Enums\CareerKey;
use App\Domain\Enums\LifeStateKey;
use App\Domain\Enums\NpcIntent;

final class NpcBehaviorProfile
{
    /** @var list<string> */
    public array $intents;
    public string $posture;
    public bool $willAcceptSpiritualOffice;
    public bool $willAcceptSecularOffice;
    public bool $hostileToChurch;

    public function __construct(
        array $intents,
        string $posture,
        bool $willAcceptSpiritualOffice,
        bool $willAcceptSecularOffice,
        bool $hostileToChurch
    ) {
        $this->intents = $intents;
        $this->posture = $posture;
        $this->willAcceptSpiritualOffice = $willAcceptSpiritualOffice;
        $this->willAcceptSecularOffice = $willAcceptSecularOffice;
        $this->hostileToChurch = $hostileToChurch;
    }

    public static function from(CareerPerson $person): self
    {
        $intents = [];
        if ($person->career !== CareerKey::NONE) {
            $intents = CareerCatalog::get($person->career)['intents'];
        }

        $posture = 'ordinary';
        $spiritual = CareerKey::isClerical($person->career) && !$person->hasLifeState(LifeStateKey::HERETIC);
        $secular = !LifeStateRules::blocksSecularInheritance($person->lifeStates);
        $hostile = false;

        if ($person->hasLifeState(LifeStateKey::IMPRISONED)) {
            $intents = [NpcIntent::ENDURE_CAPTIVITY];
            $posture = 'captive';
            $spiritual = false;
            $secular = false;
        } elseif ($person->hasLifeState(LifeStateKey::EXILE)) {
            $intents[] = NpcIntent::PLOT_RETURN;
            $posture = 'exile';
            if ($person->hasLifeState(LifeStateKey::CLAIMANT) || $person->hasClaim) {
                $intents[] = NpcIntent::PRESS_CLAIM;
                $posture = 'exiled_claimant';
            }
        }

        if ($person->hasLifeState(LifeStateKey::HERETIC)) {
            $intents[] = NpcIntent::PREACH_HERESY;
            $spiritual = false;
            $hostile = true;
        }
        if ($person->hasLifeState(LifeStateKey::CULT_MEMBER)) {
            $intents[] = NpcIntent::SERVE_CULT;
            $hostile = true;
        }
        if ($person->hasLifeState(LifeStateKey::HOLY_ORDER)) {
            $intents[] = NpcIntent::CRUSADE;
        }
        if ($person->hasLifeState(LifeStateKey::HERMIT)) {
            $intents = [NpcIntent::WITHDRAW, NpcIntent::PRAY];
            $posture = 'withdrawn';
            $secular = false;
        }

        $intents = array_values(array_unique($intents));

        return new self($intents, $posture, $spiritual, $secular, $hostile);
    }

    public function has(string $intent): bool
    {
        return in_array($intent, $this->intents, true);
    }
}
