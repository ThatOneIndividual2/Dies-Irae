<?php

namespace App\Domain\Warfare\Ports;

use App\Domain\Warfare\State\AftermathReport;

final class NullAftermathSink implements AftermathSink
{
    public function apply(AftermathReport $report): void
    {
    }
}
