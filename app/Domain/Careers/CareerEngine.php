<?php

namespace App\Domain\Careers;

use App\Domain\Enums\CareerKey;
use App\Domain\Enums\LifeEventKey;
use App\Domain\Enums\LifeStateKey;
use App\Domain\Enums\SkillKey;
use App\Domain\Enums\TitleRank;

final class CareerEngine
{
    public function person(int $worldId, string $id, string $name, int $age, array $skills = []): CareerPerson
    {
        return new CareerPerson($worldId, $id, $name, $age, new CharacterSkills($skills));
    }

    public function completeEducation(CareerPerson $person, string $source): CareerPerson
    {
        $def = EducationCatalog::get($source);
        if (!in_array($source, $person->educations, true)) {
            $person->educations[] = $source;
            $person->skills->bumpMany($def['skills']);
            $person->record('education', $source);
        }

        return $person;
    }

    public function assignCareer(CareerPerson $person, string $career, bool $forced = false): CareerPerson
    {
        if (!CareerKey::isKnown($career)) {
            throw new CareerException("Unknown career {$career}");
        }

        if (!$forced && !CareerCatalog::mayAdvance($person->career, $career)) {
            throw new CareerException("Cannot move from {$person->career} to {$career}");
        }

        if ($career !== CareerKey::NONE) {
            $def = CareerCatalog::get($career);
            if ($person->age < $def['min_age'] && !$forced) {
                throw new CareerException("Too young for {$career}");
            }
            if ($career === CareerKey::PRIEST && !$person->hasLifeState(LifeStateKey::ORDAINED) && !$forced) {
                throw new CareerException('Priest career requires ordination');
            }
            if (CareerKey::isClerical($career) && $person->hasLifeState(LifeStateKey::MARRIED) && !$forced) {
                throw new CareerException('Married persons cannot take a clerical career');
            }
        }

        $person->careerHistory[] = ['career' => $person->career, 'at_age' => $person->age];
        $person->career = $career;
        if ($career !== CareerKey::NONE) {
            foreach (CareerCatalog::get($career)['skills'] as $skill) {
                $person->skills->bump($skill, 1);
            }
            if (CareerKey::isClerical($career)) {
                $person->addLifeState(LifeStateKey::IN_CLERGY);
            }
        }
        $person->record('career', $career);

        return $person;
    }

    public function apply(CareerPerson $person, string $event, array $payload = []): CareerPerson
    {
        return match ($event) {
            LifeEventKey::ENTER_CLERGY => $this->enterClergy($person),
            LifeEventKey::LEAVE_ROLE => $this->leaveRole($person, (string) ($payload['to'] ?? CareerKey::NONE)),
            LifeEventKey::TAKE_VOWS => $this->takeVows($person, (bool) ($payload['solemn'] ?? false)),
            LifeEventKey::MARRY => $this->marry($person),
            LifeEventKey::WIDOW => $this->widow($person),
            LifeEventKey::INHERIT => $this->inherit($person, (string) ($payload['rank'] ?? TitleRank::COUNTY)),
            LifeEventKey::ORDAIN => $this->ordain($person),
            LifeEventKey::RECEIVE_BENEFICE => $this->receiveBenefice($person),
            LifeEventKey::LOSE_OFFICE => $this->loseOffice($person),
            LifeEventKey::IMPRISON => $this->imprison($person),
            LifeEventKey::EXILE => $this->exile($person, (bool) ($payload['claimant'] ?? false)),
            LifeEventKey::JOIN_HOLY_ORDER => $this->joinHolyOrder($person),
            LifeEventKey::BECOME_HERMIT => $this->becomeHermit($person),
            LifeEventKey::BECOME_HERETIC => $this->becomeHeretic($person),
            LifeEventKey::JOIN_CULT => $this->joinCult($person),
            LifeEventKey::RELEASE => $this->release($person),
            LifeEventKey::RETURN_FROM_EXILE => $this->returnFromExile($person),
            default => throw new CareerException("Unknown life event {$event}"),
        };
    }

    public function enterClergy(CareerPerson $person): CareerPerson
    {
        if ($person->hasLifeState(LifeStateKey::MARRIED)) {
            throw new CareerException('Cannot enter clergy while married');
        }
        $person->addLifeState(LifeStateKey::IN_CLERGY);
        if ($person->career === CareerKey::NONE || !CareerKey::isClerical($person->career)) {
            $target = CareerKey::NOVICE;
            if (in_array($person->career, [CareerKey::SCHOLAR], true) && $person->age >= 24) {
                $target = CareerKey::NOVICE;
            }
            $this->assignCareer($person, $target, true);
        }
        $person->record('life', LifeEventKey::ENTER_CLERGY);

        return $person;
    }

    public function leaveRole(CareerPerson $person, string $to = CareerKey::NONE): CareerPerson
    {
        $def = $person->career === CareerKey::NONE ? ['may_leave' => true] : CareerCatalog::get($person->career);
        if (!LifeStateRules::mayLeaveClergy($person->lifeStates, (bool) $def['may_leave'])) {
            throw new CareerException("Cannot leave {$person->career}");
        }
        if (CareerKey::isClerical($person->career) && !$person->hasLifeState(LifeStateKey::ORDAINED)) {
            $person->endLifeState(LifeStateKey::IN_CLERGY);
            $person->endLifeState(LifeStateKey::SIMPLE_VOWS);
        }
        $this->assignCareer($person, $to, true);
        $person->record('life', LifeEventKey::LEAVE_ROLE);

        return $person;
    }

    public function takeVows(CareerPerson $person, bool $solemn = false): CareerPerson
    {
        if (LifeStateRules::blocksMarriage($person->lifeStates) && $person->hasLifeState(LifeStateKey::MARRIED)) {
            throw new CareerException('Cannot take vows while married');
        }
        if ($person->hasLifeState(LifeStateKey::MARRIED)) {
            throw new CareerException('Cannot take vows while married');
        }
        if (!$person->isCleric() && $person->career !== CareerKey::KNIGHT) {
            throw new CareerException('Vows require clergy or a knight entering religion');
        }
        $person->endLifeState(LifeStateKey::MARRIED);
        $person->addLifeState($solemn ? LifeStateKey::SOLEMN_VOWS : LifeStateKey::SIMPLE_VOWS);
        $person->skills->bump(SkillKey::PIETY_REPUTATION, $solemn ? 2 : 1);
        $person->record('life', LifeEventKey::TAKE_VOWS);

        return $person;
    }

    public function marry(CareerPerson $person): CareerPerson
    {
        if (LifeStateRules::blocksMarriage($person->lifeStates)) {
            throw new CareerException('Vows or orders forbid marriage');
        }
        $person->endLifeState(LifeStateKey::WIDOWED);
        $person->addLifeState(LifeStateKey::MARRIED);
        $person->record('life', LifeEventKey::MARRY);

        return $person;
    }

    public function widow(CareerPerson $person): CareerPerson
    {
        if (!$person->hasLifeState(LifeStateKey::MARRIED)) {
            throw new CareerException('Cannot be widowed without marriage');
        }
        $person->endLifeState(LifeStateKey::MARRIED);
        $person->addLifeState(LifeStateKey::WIDOWED);
        $person->record('life', LifeEventKey::WIDOW);

        return $person;
    }

    public function inherit(CareerPerson $person, string $rank): CareerPerson
    {
        if (LifeStateRules::blocksSecularInheritance($person->lifeStates)) {
            throw new CareerException('Vows or orders block secular inheritance');
        }
        $person->titleRank = $rank;
        $person->hasClaim = false;
        $person->endLifeState(LifeStateKey::CLAIMANT);
        if (TitleRank::weight($rank) >= TitleRank::weight(TitleRank::BARONY)) {
            $this->assignCareer($person, CareerKey::LANDED_NOBLE, true);
        }
        $person->record('life', LifeEventKey::INHERIT.':'.$rank);

        return $person;
    }

    public function ordain(CareerPerson $person): CareerPerson
    {
        if (!$person->isCleric() && !in_array($person->career, [CareerKey::NOVICE, CareerKey::MONK, CareerKey::SCHOLAR], true)) {
            throw new CareerException('Ordination requires a clerical path');
        }
        if ($person->hasLifeState(LifeStateKey::MARRIED)) {
            throw new CareerException('Cannot ordain a married person');
        }
        $person->addLifeState(LifeStateKey::ORDAINED);
        $person->addLifeState(LifeStateKey::IN_CLERGY);
        $person->skills->bump(SkillKey::THEOLOGY, 1);
        $person->skills->bump(SkillKey::PIETY_REPUTATION, 2);
        if (!in_array($person->career, [CareerKey::PRIEST, CareerKey::ABBOT, CareerKey::BISHOP, CareerKey::ARCHBISHOP, CareerKey::CARDINAL, CareerKey::CANON, CareerKey::CHAPLAIN, CareerKey::THEOLOGIAN, CareerKey::INQUISITOR, CareerKey::EXORCIST, CareerKey::PAPAL_OFFICIAL], true)) {
            $this->assignCareer($person, CareerKey::PRIEST, true);
        }
        $person->record('life', LifeEventKey::ORDAIN);

        return $person;
    }

    public function receiveBenefice(CareerPerson $person): CareerPerson
    {
        if (!$person->hasLifeState(LifeStateKey::ORDAINED) && !CareerKey::isClerical($person->career)) {
            throw new CareerException('Benefices are for clergy');
        }
        $person->addLifeState(LifeStateKey::BENEFICED);
        $person->record('life', LifeEventKey::RECEIVE_BENEFICE);

        return $person;
    }

    public function loseOffice(CareerPerson $person): CareerPerson
    {
        $person->endLifeState(LifeStateKey::BENEFICED);
        if ($person->titleRank !== null && !CareerKey::isClerical($person->career)) {
            $person->titleRank = null;
            if ($person->career === CareerKey::LANDED_NOBLE) {
                $this->assignCareer($person, CareerKey::COURTIER, true);
            }
        }
        $person->skills->bump(SkillKey::PIETY_REPUTATION, CareerKey::isClerical($person->career) ? -2 : 0);
        $person->record('life', LifeEventKey::LOSE_OFFICE);

        return $person;
    }

    public function imprison(CareerPerson $person): CareerPerson
    {
        $person->addLifeState(LifeStateKey::IMPRISONED);
        $person->skills->bump(SkillKey::PIETY_REPUTATION, -1);
        $person->record('life', LifeEventKey::IMPRISON);

        return $person;
    }

    public function exile(CareerPerson $person, bool $claimant = false): CareerPerson
    {
        $person->addLifeState(LifeStateKey::EXILE);
        if ($claimant || $person->titleRank !== null) {
            $person->hasClaim = true;
            $person->addLifeState(LifeStateKey::CLAIMANT);
        }
        if ($person->titleRank !== null) {
            $person->titleRank = null;
            if ($person->career === CareerKey::LANDED_NOBLE) {
                $this->assignCareer($person, CareerKey::COURTIER, true);
            }
        }
        $person->record('life', LifeEventKey::EXILE);

        return $person;
    }

    public function joinHolyOrder(CareerPerson $person): CareerPerson
    {
        if (!in_array($person->career, [CareerKey::KNIGHT, CareerKey::SQUIRE, CareerKey::MERCENARY_CAPTAIN], true)) {
            throw new CareerException('Holy orders of knighthood expect a martial career');
        }
        if ($person->hasLifeState(LifeStateKey::MARRIED)) {
            throw new CareerException('Cannot join a holy order while married');
        }
        $this->takeVows($person, true);
        $person->addLifeState(LifeStateKey::HOLY_ORDER);
        $person->addLifeState(LifeStateKey::IN_CLERGY);
        $person->skills->bump(SkillKey::PIETY_REPUTATION, 2);
        $person->skills->bump(SkillKey::MARTIAL, 1);
        $person->record('life', LifeEventKey::JOIN_HOLY_ORDER);

        return $person;
    }

    public function becomeHermit(CareerPerson $person): CareerPerson
    {
        $person->addLifeState(LifeStateKey::HERMIT);
        $person->skills->bump(SkillKey::PIETY_REPUTATION, 3);
        $person->record('life', LifeEventKey::BECOME_HERMIT);

        return $person;
    }

    public function becomeHeretic(CareerPerson $person): CareerPerson
    {
        $person->addLifeState(LifeStateKey::HERETIC);
        $person->skills->bump(SkillKey::PIETY_REPUTATION, -8);
        $person->endLifeState(LifeStateKey::BENEFICED);
        $person->record('life', LifeEventKey::BECOME_HERETIC);

        return $person;
    }

    public function joinCult(CareerPerson $person): CareerPerson
    {
        $person->addLifeState(LifeStateKey::CULT_MEMBER);
        $person->skills->bump(SkillKey::PIETY_REPUTATION, -6);
        $person->record('life', LifeEventKey::JOIN_CULT);

        return $person;
    }

    public function release(CareerPerson $person): CareerPerson
    {
        $person->endLifeState(LifeStateKey::IMPRISONED);
        $person->record('life', LifeEventKey::RELEASE);

        return $person;
    }

    public function returnFromExile(CareerPerson $person): CareerPerson
    {
        $person->endLifeState(LifeStateKey::EXILE);
        $person->record('life', LifeEventKey::RETURN_FROM_EXILE);

        return $person;
    }

    public function behavior(CareerPerson $person): NpcBehaviorProfile
    {
        return NpcBehaviorProfile::from($person);
    }

    public function suitability(CareerPerson $person, string $kind, string $rank): AppointmentSuitability
    {
        return AppointmentSuitability::evaluate($person, $kind, $rank);
    }
}
