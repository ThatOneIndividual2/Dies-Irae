<?php

namespace App\Domain\Sacred\Ports;

use App\Domain\Church\ChurchAuthority;
use App\Domain\Church\ChurchException;
use App\Models\Character;

final class ChurchSacredAuthority implements SacredAuthorityPort
{
    public function __construct(private ChurchAuthority $church)
    {
    }

    public function mayRecognizeSaint(Character $actor): bool
    {
        return $this->can($actor, 'recognize_saint');
    }

    public function mayConfirmCultus(Character $actor): bool
    {
        return $this->can($actor, 'recognize_saint') || $this->can($actor, 'recognize_relic');
    }

    public function mayRecognizeRelic(Character $actor): bool
    {
        return $this->can($actor, 'recognize_relic');
    }

    public function mayRecognizeMiracle(Character $actor): bool
    {
        return $this->can($actor, 'recognize_saint') || $this->can($actor, 'recognize_relic');
    }

    private function can(Character $actor, string $power): bool
    {
        try {
            $this->church->assertPower($actor, $power);
            return true;
        } catch (ChurchException $e) {
            return false;
        }
    }
}
