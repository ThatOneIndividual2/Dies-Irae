<?php

namespace App\Domain\Ai\Reasoners;

use App\Domain\Ai\Enums\ActorType;
use InvalidArgumentException;

final class ReasonerRegistry
{
    /** @var array<string, Reasoner> */
    private array $reasoners = [];

    public static function standard(): self
    {
        $r = new self();
        foreach ([
            new KingReasoner(),
            new DukeReasoner(),
            new CountReasoner(),
            new BishopReasoner(),
            new PopeReasoner(),
            new AbbotReasoner(),
            new HolyOrderLeaderReasoner(),
            new MilitaryCommanderReasoner(),
            new ClaimantReasoner(),
            new MerchantReasoner(),
            new HereticLeaderReasoner(),
            new CultLeaderReasoner(),
            new DemonCommanderReasoner(),
            new RealmReasoner(),
            new MonasteryReasoner(),
            new HolyOrderReasoner(),
            new CultOrganizationReasoner(),
            new PapacyReasoner(),
        ] as $reasoner) {
            $r->register($reasoner);
        }

        foreach (ActorType::all() as $type) {
            if (!isset($r->reasoners[$type])) {
                throw new InvalidArgumentException("No reasoner registered for {$type}");
            }
        }

        return $r;
    }

    public function register(Reasoner $reasoner): void
    {
        $this->reasoners[$reasoner->actorType()] = $reasoner;
    }

    public function get(string $type): Reasoner
    {
        if (!isset($this->reasoners[$type])) {
            throw new InvalidArgumentException("No reasoner for actor type {$type}");
        }

        return $this->reasoners[$type];
    }
}
