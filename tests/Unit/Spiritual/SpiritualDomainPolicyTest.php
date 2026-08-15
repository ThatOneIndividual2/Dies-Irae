<?php

namespace Tests\Unit\Spiritual;

use App\Domain\Enums\CapitalVice;
use App\Domain\Enums\CorruptionSubjectType;
use App\Domain\Enums\SpiritualActType;
use App\Domain\Enums\SpiritualObserverContext;
use App\Domain\Enums\TheologicalVirtue;
use App\Domain\Spiritual\Catalogs\SpiritualActCatalog;
use App\Domain\Spiritual\Policies\ConfessionSealPolicy;
use App\Domain\Spiritual\Policies\CorruptionPolicyRegistry;
use App\Domain\Spiritual\Policies\SpiritualVisibilityPolicy;
use App\Models\CharacterCanonicalState;
use App\Models\CharacterSpiritualState;
use App\Models\CorruptionState;
use DomainException;
use Tests\Feature\LaravelTestCase;

class SpiritualDomainPolicyTest extends LaravelTestCase
{
    public function test_murder_and_martyrdom_are_not_the_same_meter(): void
    {
        $catalog = new SpiritualActCatalog(config('spiritual.act_effects'));

        $murder = $catalog->signature(SpiritualActType::MURDER);
        $martyrdom = $catalog->signature(SpiritualActType::MARTYRDOM);

        $this->assertNotSame($murder, $martyrdom);
        $this->assertGreaterThan(1, count($murder));
        $this->assertGreaterThan(1, count($martyrdom));
        $this->assertGreaterThan(0, $murder[CapitalVice::WRATH]);
        $this->assertLessThan(0, $murder[TheologicalVirtue::CHARITY]);
        $this->assertGreaterThan(0, $martyrdom[TheologicalVirtue::FAITH]);
        $this->assertGreaterThan(0, $martyrdom[TheologicalVirtue::CHARITY]);
        $this->assertArrayNotHasKey(CapitalVice::WRATH, $martyrdom);
    }

    public function test_no_act_collapses_to_a_single_axis(): void
    {
        $catalog = new SpiritualActCatalog(config('spiritual.act_effects'));
        foreach (SpiritualActType::all() as $act) {
            $this->assertGreaterThan(
                1,
                count($catalog->deltas($act)),
                $act.' must move more than one spiritual axis'
            );
        }
    }

    public function test_interior_scores_are_private(): void
    {
        $policy = new SpiritualVisibilityPolicy();
        $this->assertSame('private', $policy->classForFacet(CapitalVice::WRATH));
        $this->assertSame('private', $policy->classForFacet(TheologicalVirtue::FAITH));
        $this->assertSame('private', $policy->classForFacet('grave_unconfessed'));
        $this->assertSame('public', $policy->classForFacet('excommunication'));
        $this->assertSame('sealed', $policy->classForFacet('confessed_matter'));
        $this->assertFalse($policy->allowsExactScores(SpiritualObserverContext::PUBLIC));
        $this->assertTrue($policy->allowsExactScores(SpiritualObserverContext::ADMIN));
    }

    public function test_inferable_bands_hide_numbers(): void
    {
        $policy = new SpiritualVisibilityPolicy();
        $state = new CharacterSpiritualState([
            'wrath' => 82,
            'pride' => 10,
            'greed' => 10,
            'lust' => 10,
            'envy' => 10,
            'gluttony' => 10,
            'sloth' => 10,
            'despair' => 5,
        ]);

        $bands = $policy->inferableFrom($state, null);
        $this->assertSame('marked', $bands['wrath']);
        $this->assertArrayNotHasKey('pride', $bands);
        $this->assertSame('marked', $policy->inferableBand(82, 70));
        $this->assertNull($policy->inferableBand(20, 70));
    }

    public function test_confession_seal_blocks_political_use(): void
    {
        $seal = new ConfessionSealPolicy();
        $row = ['is_sealed' => true, 'facet' => 'confessed_matter'];
        $this->assertFalse($seal->mayRevealInContext(SpiritualObserverContext::POLITICAL, $row));
        $this->assertTrue($seal->mayRevealInContext(SpiritualObserverContext::CONFESSOR, $row));

        $this->expectException(DomainException::class);
        $seal->assertNotPoliticalUse(SpiritualObserverContext::POLITICAL, $row);
    }

    public function test_corruption_policies_are_not_one_table_of_effects(): void
    {
        $registry = new CorruptionPolicyRegistry();
        $army = new CorruptionState(['intensity' => 40, 'kind' => 'infernal_taint']);
        $monastery = new CorruptionState(['intensity' => 40, 'kind' => 'spiritual_rot']);

        $armyMods = $registry->for(CorruptionSubjectType::ARMY)->gameplayModifiers($army);
        $monkMods = $registry->for(CorruptionSubjectType::MONASTERY)->gameplayModifiers($monastery);

        $this->assertArrayHasKey('morale', $armyMods);
        $this->assertArrayHasKey('liturgy_quality', $monkMods);
        $this->assertArrayNotHasKey('liturgy_quality', $armyMods);
        $this->assertArrayNotHasKey('morale', $monkMods);
        $this->assertSame(CorruptionSubjectType::all(), $registry->registeredTypes());
    }

    public function test_public_canonical_includes_censure_not_wrath_score(): void
    {
        $policy = new SpiritualVisibilityPolicy();
        $canonical = new CharacterCanonicalState([
            'is_baptized' => true,
            'holy_orders_grade' => 'none',
            'matrimonial_bond_character_id' => null,
            'censure' => 'excommunication',
        ]);
        $public = $policy->publicCanonical($canonical, []);
        $this->assertTrue($public['excommunication']);
        $this->assertArrayNotHasKey('wrath', $public);
        $this->assertArrayNotHasKey('faith', $public);
    }
}
