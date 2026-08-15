<?php

namespace Tests\Feature\Catastrophe;

use App\Actions\Catastrophe\SetQuarantine;
use App\Domain\Enums\QuarantineLevel;
use App\Domain\Enums\SpreadVector;
use Tests\Support\WorldFixture;
use Tests\DomainTestCase;

class PlagueTradeSpreadTest extends DomainTestCase
{
    public function test_plague_spreads_along_a_trade_link(): void
    {
        $engine = WorldFixture::engine();
        WorldFixture::city($engine, 'genoa', 'Genoa');
        WorldFixture::city($engine, 'marseille', 'Marseille');
        $engine->link('genoa', 'marseille', SpreadVector::TRADE, 8500);
        $engine->seedPlague('genoa', 1000);

        $engine->tick(1);

        $port = $engine->settlement('marseille');
        $this->assertGreaterThan(0, $port->incubating + $port->infectious);
        $engine->assertNonNegative();
    }

    public function test_closed_gates_reduce_trade_spread(): void
    {
        $open = WorldFixture::engine();
        WorldFixture::city($open, 'genoa', 'Genoa');
        WorldFixture::city($open, 'marseille', 'Marseille');
        $open->link('genoa', 'marseille', SpreadVector::TRADE, 8500);
        $open->seedPlague('genoa', 1000);
        $open->tick(1);
        $openSeed = $open->settlement('marseille')->incubating;

        $shut = WorldFixture::engine();
        WorldFixture::city($shut, 'genoa', 'Genoa');
        WorldFixture::city($shut, 'marseille', 'Marseille');
        $shut->link('genoa', 'marseille', SpreadVector::TRADE, 8500);
        (new SetQuarantine())->execute($shut, 'marseille', QuarantineLevel::CLOSED_GATES);
        $shut->seedPlague('genoa', 1000);
        $shut->tick(1);
        $shutSeed = $shut->settlement('marseille')->incubating;

        $this->assertGreaterThan($shutSeed, $openSeed);
        $this->assertGreaterThan(0, $openSeed);
    }

    public function test_cordon_blocks_more_trade_than_watch(): void
    {
        $watch = WorldFixture::engine();
        WorldFixture::city($watch, 'genoa', 'Genoa');
        WorldFixture::city($watch, 'marseille', 'Marseille');
        $watch->link('genoa', 'marseille', SpreadVector::TRADE, 8500);
        $watch->settlement('marseille')->quarantine = QuarantineLevel::WATCH;
        $watch->seedPlague('genoa', 1000);
        $watch->tick(1);

        $cordon = WorldFixture::engine();
        WorldFixture::city($cordon, 'genoa', 'Genoa');
        WorldFixture::city($cordon, 'marseille', 'Marseille');
        $cordon->link('genoa', 'marseille', SpreadVector::TRADE, 8500);
        $cordon->settlement('marseille')->quarantine = QuarantineLevel::CORDON;
        $cordon->seedPlague('genoa', 1000);
        $cordon->tick(1);

        $this->assertGreaterThan(
            $cordon->settlement('marseille')->incubating,
            $watch->settlement('marseille')->incubating
        );
    }
}
