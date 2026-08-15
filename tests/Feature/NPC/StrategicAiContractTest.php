<?php

namespace Tests\Feature\NPC;

use App\Domain\Ai\Enums\ActorType;
use App\Domain\NPC\NPCContract;
use Tests\Feature\LaravelTestCase;
use Tests\Unit\Ai\StrategicAiHarness;

final class StrategicAiContractTest extends LaravelTestCase
{
    public function test_npc_contract_dispatches_typed_reasoners(): void
    {
        $npc = app(NPCContract::class);
        $world = StrategicAiHarness::incursion();

        $king = $npc->consider(StrategicAiHarness::actor('louis', ActorType::KING), $world);
        $bishop = $npc->consider(StrategicAiHarness::actor('hugues', ActorType::BISHOP), $world);

        $this->assertSame('npc', $npc->domainKey());
        $this->assertNotSame($king->actionKey, $bishop->actionKey);
        $this->assertStringContainsString('chosen:', $npc->explain($king)->explain());
    }
}
