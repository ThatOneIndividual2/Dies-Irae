<?php

namespace App\Domain\Papacy;

final class PapalSeeCondition
{
    public string $seeId = 'rome';
    public string $seeName = 'Rome';
    public bool $accessible = true;
    public bool $occupied = false;
    public bool $demonicIncursion = false;
    public bool $plague = false;
    public bool $communicationsDestroyed = false;
    public ?string $fallbackSeeId = 'avignon';
    public ?string $fallbackSeeName = 'Avignon';

    public function assemblySeat(): array
    {
        if ($this->accessible && !$this->occupied && !$this->demonicIncursion) {
            return ['id' => $this->seeId, 'name' => $this->seeName, 'relocated' => false];
        }

        return [
            'id' => $this->fallbackSeeId ?? $this->seeId,
            'name' => $this->fallbackSeeName ?? $this->seeName,
            'relocated' => true,
        ];
    }

    public function delaysAssembly(): bool
    {
        return !$this->accessible
            || $this->occupied
            || $this->demonicIncursion
            || $this->plague
            || $this->communicationsDestroyed;
    }
}
