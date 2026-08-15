<?php

namespace App\Domain\Warfare\Ports;

use App\Domain\Warfare\State\AftermathReport;

final class RecordingAftermathSink implements AftermathSink
{
    /** @var AftermathReport[] */
    public array $reports = [];

    public function apply(AftermathReport $report): void
    {
        $this->reports[] = $report;
    }
}
