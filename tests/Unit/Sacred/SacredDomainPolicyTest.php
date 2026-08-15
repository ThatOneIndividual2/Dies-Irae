<?php

namespace Tests\Unit\Sacred;

use App\Domain\Enums\RelicTrueNature;
use App\Domain\Sacred\Policies\MiracleRarityPolicy;
use App\Domain\Sacred\Policies\RelicVisibilityPolicy;
use App\Models\Relic;
use Carbon\Carbon;
use DomainException;
use Tests\Feature\LaravelTestCase;

class SacredDomainPolicyTest extends LaravelTestCase
{
    public function test_miracle_claims_need_a_source_event(): void
    {
        $policy = new MiracleRarityPolicy();
        $this->expectException(DomainException::class);
        $policy->assertClaimAllowed(1, 'territory', 1, 'healing', '', Carbon::parse('1348-01-01'));
    }

    public function test_there_is_no_castable_miracle_action(): void
    {
        $this->assertFalse(class_exists(\App\Actions\Sacred\CastMiracle::class));
        $this->assertTrue(class_exists(\App\Actions\Sacred\RecordMiracleClaim::class));
    }

    public function test_relic_true_nature_is_not_public(): void
    {
        $relic = new Relic([
            'name' => 'Finger of Denis',
            'category' => 'bodily',
            'claimed_authenticity' => 'recognized',
            'authenticity' => 'unrecognized',
            'true_nature' => RelicTrueNature::FORGED,
            'claimed_provenance' => 'found in a market',
            'condition' => 'intact',
            'pilgrimage_value' => 12,
        ]);
        $view = (new RelicVisibilityPolicy())->publicView($relic);
        $this->assertArrayNotHasKey('true_nature', $view);
        $this->assertSame(RelicTrueNature::FORGED, (new RelicVisibilityPolicy())->adminView($relic)['true_nature']);
        $this->assertArrayNotHasKey('true_nature', $relic->toArray());
    }
}
