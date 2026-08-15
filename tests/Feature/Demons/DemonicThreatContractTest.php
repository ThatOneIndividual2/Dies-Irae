<?php

namespace Tests\Feature\Demons;

use App\Domain\Demons\DemonsContract;
use App\Domain\Hell\Enums\IncursionState;
use Tests\Feature\LaravelTestCase;
use Tests\Unit\Hell\DemonicThreatHarness;

final class DemonicThreatContractTest extends LaravelTestCase
{
    public function test_demons_contract_is_not_a_realm_and_can_pulse(): void
    {
        $world = DemonicThreatHarness::ordinaryCounties();
        $world->apocalypseIntensity = 12;
        $world->territory('center')->incursionState = IncursionState::TEMPTED;
        DemonicThreatHarness::ripeForCorruption($world, 'center');

        $result = app(DemonsContract::class)->pulse($world);

        $this->assertSame('demons', app(DemonsContract::class)->domainKey());
        $this->assertSame(IncursionState::CORRUPTED, $result->world->territory('center')->incursionState);
        $this->assertSame('county-center', $result->world->territory('center')->legalTitleId);
        $result->world->assertNotARealm();
    }
}
