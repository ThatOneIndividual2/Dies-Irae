<?php

namespace App\Domain\Warfare\Ports;

use App\Domain\Warfare\State\AftermathReport;

/**
 * Downstream sink so warfare never owns population, church, or hell tables.
 */
interface AftermathSink
{
    public function apply(AftermathReport $report): void;
}
