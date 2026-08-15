<?php

namespace App\Domain\Sacred\Ports;

use App\Models\Character;

final class InMemorySacredAuthority implements SacredAuthorityPort
{
    /** @var array<int, array{saint:bool, cultus:bool, relic:bool, miracle:bool}> */
    private array $grants = [];

    public function grant(
        Character $character,
        bool $saint = true,
        bool $cultus = true,
        bool $relic = true,
        bool $miracle = true
    ): void {
        $this->grants[(int) $character->id] = [
            'saint' => $saint,
            'cultus' => $cultus,
            'relic' => $relic,
            'miracle' => $miracle,
        ];
    }

    public function mayRecognizeSaint(Character $actor): bool
    {
        return (bool) ($this->grants[(int) $actor->id]['saint'] ?? false);
    }

    public function mayConfirmCultus(Character $actor): bool
    {
        return (bool) ($this->grants[(int) $actor->id]['cultus'] ?? false);
    }

    public function mayRecognizeRelic(Character $actor): bool
    {
        return (bool) ($this->grants[(int) $actor->id]['relic'] ?? false);
    }

    public function mayRecognizeMiracle(Character $actor): bool
    {
        return (bool) ($this->grants[(int) $actor->id]['miracle'] ?? false);
    }
}
