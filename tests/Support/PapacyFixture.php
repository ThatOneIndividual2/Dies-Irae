<?php

namespace Tests\Support;

use App\Domain\Enums\CardinalFaction;
use App\Domain\Enums\ConclavePhase;
use App\Domain\Enums\PapacyStatus;
use App\Domain\Enums\TheologicalLean;
use App\Domain\Papacy\CardinalElector;
use App\Domain\Papacy\PapacyEngine;

final class PapacyFixture
{
    public static function engine(int $worldId = 1, string $date = '1348-12-06'): PapacyEngine
    {
        $engine = new PapacyEngine($worldId, $date);
        $engine->popeId = 'clement';
        $engine->papacyStatus = PapacyStatus::OCCUPIED;
        $engine->papalOfficeId = 'papal-office';

        return $engine;
    }

    public static function cardinal(
        string $id,
        string $name,
        string $faction,
        string $theology,
        array $preferences,
        int $reputation = 50,
        ?string $realmTie = null,
        int $ambition = 30,
        int $corruption = 20,
        int $fear = 20
    ): CardinalElector {
        $cardinal = new CardinalElector($id, $name);
        $cardinal->faction = $faction;
        $cardinal->theology = $theology;
        $cardinal->preferences = $preferences;
        $cardinal->theologicalReputation = $reputation;
        $cardinal->realmTie = $realmTie;
        $cardinal->ambition = $ambition;
        $cardinal->corruption = $corruption;
        $cardinal->fear = $fear;
        $cardinal->clampTraits();

        return $cardinal;
    }

    public static function majorityCollege(PapacyEngine $engine): void
    {
        $orsini = ['orsini' => 90, 'foix' => 10];
        $foix = ['orsini' => 10, 'foix' => 90];

        $engine->addCardinal(self::cardinal('orsini', 'Orsini', CardinalFaction::ITALIAN, TheologicalLean::CONSERVATIVE, $orsini, 70, 'italy'));
        foreach (['ita-1', 'ita-2', 'ita-3', 'ita-4', 'ita-5', 'ita-6'] as $id) {
            $engine->addCardinal(self::cardinal($id, $id, CardinalFaction::ITALIAN, TheologicalLean::CONSERVATIVE, $orsini, 40, 'italy'));
        }
        $engine->addCardinal(self::cardinal('foix', 'Foix', CardinalFaction::FRENCH, TheologicalLean::WORLDLY, $foix, 50, 'france', 35, 25));
        $engine->addCardinal(self::cardinal('fra-1', 'Amiens', CardinalFaction::FRENCH, TheologicalLean::WORLDLY, $foix, 40, 'france', 25, 30));
    }

    public static function deadlockCollege(PapacyEngine $engine): void
    {
        $orsini = ['orsini' => 80, 'foix' => 10, 'colonna' => 10];
        $foix = ['orsini' => 10, 'foix' => 80, 'colonna' => 10];
        $colonna = ['orsini' => 10, 'foix' => 10, 'colonna' => 80];

        $engine->addCardinal(self::cardinal('orsini', 'Orsini', CardinalFaction::ITALIAN, TheologicalLean::CONSERVATIVE, $orsini, 55, 'italy'));
        $engine->addCardinal(self::cardinal('ita-1', 'Orvieto', CardinalFaction::ITALIAN, TheologicalLean::CONSERVATIVE, $orsini, 40, 'italy'));
        $engine->addCardinal(self::cardinal('ita-2', 'Perugia', CardinalFaction::ITALIAN, TheologicalLean::CONSERVATIVE, $orsini, 40, 'italy'));

        $engine->addCardinal(self::cardinal('foix', 'Foix', CardinalFaction::FRENCH, TheologicalLean::WORLDLY, $foix, 50, 'france'));
        $engine->addCardinal(self::cardinal('fra-1', 'Amiens', CardinalFaction::FRENCH, TheologicalLean::WORLDLY, $foix, 40, 'france'));
        $engine->addCardinal(self::cardinal('fra-2', 'Sens', CardinalFaction::FRENCH, TheologicalLean::WORLDLY, $foix, 40, 'france'));

        $engine->addCardinal(self::cardinal('colonna', 'Colonna', CardinalFaction::CURIAL, TheologicalLean::MYSTIC, $colonna, 90, null, 25));
        $engine->addCardinal(self::cardinal('cur-1', 'Ostia', CardinalFaction::CURIAL, TheologicalLean::MYSTIC, $colonna, 45));
        $engine->addCardinal(self::cardinal('cur-2', 'Sabina', CardinalFaction::CURIAL, TheologicalLean::MYSTIC, $colonna, 45));
    }

    public static function seatCollege(PapacyEngine $engine): void
    {
        $engine->assemble();
        if ($engine->conclave->phase === ConclavePhase::ASSEMBLING) {
            $engine->tick();
        }
    }

    public static function elect(PapacyEngine $engine, int $maxRounds = 8): void
    {
        self::seatCollege($engine);
        for ($i = 0; $i < $maxRounds; $i++) {
            if (!in_array($engine->conclave->phase, [ConclavePhase::VOTING, ConclavePhase::DEADLOCK], true)) {
                break;
            }
            $engine->voteRound();
        }
    }
}
