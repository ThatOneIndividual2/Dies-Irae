<?php

namespace App\Contracts;

use App\Models\World;

interface ApocalypseReporter
{
    /**
     * @param  array<string, mixed>  $context
     */
    public function record(
        World $world,
        string $signalKey,
        int $magnitude,
        array $context = []
    ): \App\Models\ApocalypseSignal;
}
