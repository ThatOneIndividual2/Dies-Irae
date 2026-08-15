<?php

namespace App\Domain\Ai\Constraints;

use App\Domain\Ai\Catalog\ProfileCatalog;
use App\Domain\Ai\Enums\ActorGrain;
use App\Domain\Ai\State\AiActor;
use App\Domain\Ai\State\CandidateAction;
use App\Domain\Ai\State\Situation;

final class InstitutionalGate
{
    public function __construct(private ProfileCatalog $profiles)
    {
    }

    public function reject(CandidateAction $action, AiActor $actor, Situation $situation): ?string
    {
        $type = $actor->hat();
        if (in_array($action->key, $this->profiles->forbidden($type), true)) {
            return "forbidden to {$type}";
        }

        $grain = $this->profiles->grain($type);
        if ($grain === ActorGrain::ORGANIZATION && $actor->isCharacter() && $actor->hat() === $type) {
            // character wearing an org type is a programming error
        }
        if ($actor->grain !== $grain) {
            return "grain mismatch: actor is {$actor->grain}, profile is {$grain}";
        }

        foreach ($action->requires as $need) {
            $ok = match ($need) {
                'arms' => $situation->hasArms,
                'sacrament' => $situation->hasSacrament,
                'realm_head' => $situation->isRealmHead,
                'dynasty' => $situation->isDynastic && $actor->isCharacter(),
                'papal_office' => $situation->hasPapalOffice,
                default => true,
            };
            if (!$ok) {
                return "missing requirement {$need}";
            }
        }

        if ($type === 'pope' && in_array($action->key, ['arrange_heir_marriage', 'press_claim', 'secure_succession'], true)) {
            return 'papal office may not pursue dynasty';
        }

        if ($type === 'demon_commander' && in_array('sacrament', $action->requires, true)) {
            return 'infernal actors have no sacramental authority';
        }

        if ($actor->isOrganization() && in_array($action->key, ['press_claim', 'arrange_heir_marriage'], true)) {
            return 'organizations do not press personal claims';
        }

        return (new CareerAiGate())->reject($action, $situation);
    }
}
