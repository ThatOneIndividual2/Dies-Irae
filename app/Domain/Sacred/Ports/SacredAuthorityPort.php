<?php

namespace App\Domain\Sacred\Ports;

use App\Models\Character;

interface SacredAuthorityPort
{
    public function mayRecognizeSaint(Character $actor): bool;

    public function mayConfirmCultus(Character $actor): bool;

    public function mayRecognizeRelic(Character $actor): bool;

    public function mayRecognizeMiracle(Character $actor): bool;
}
