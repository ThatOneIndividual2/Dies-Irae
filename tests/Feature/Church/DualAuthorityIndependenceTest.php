<?php

namespace Tests\Feature\Church;

use App\Actions\Church\AppointToSpiritualOffice;
use App\Actions\Church\CreatePapacy;
use App\Actions\Church\CreateSpiritualOffice;
use App\Actions\Realms\DetermineTopLiege;
use App\Actions\Titles\CreateTitle;
use App\Actions\Titles\RevokeTitle;
use App\Domain\Church\ChurchEligibilityGate;
use App\Domain\Church\DiocesanHierarchy;
use App\Domain\Enums\SeeKind;
use App\Domain\Enums\SpiritualOfficeRank;
use App\Domain\Enums\TitleRank;
use App\Models\Papacy;
use App\Models\SpiritualOfficeHoldership;
use App\Models\Title;
use App\Models\TitleOwnership;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use RuntimeException;
use Tests\Fixtures\ChurchWorldGraph;
use Tests\TestCase;

class DualAuthorityIndependenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_character_can_be_king_without_spiritual_office(): void
    {
        $g = ChurchWorldGraph::seed();

        $this->assertTrue($g->king->currentTitleOwnerships()->exists());
        $this->assertFalse($g->king->currentSpiritualHolderships()->exists());
        $this->assertFalse($g->king->currentClergyStatus()->exists());
    }

    public function test_character_can_be_bishop_without_secular_title(): void
    {
        $g = ChurchWorldGraph::seed();

        $this->assertTrue($g->bishop->currentSpiritualHolderships()->exists());
        $this->assertFalse($g->bishop->currentTitleOwnerships()->exists());
        $this->assertNull($g->bishop->dynasty_id);
    }

    public function test_prince_bishop_holds_county_and_see_independently(): void
    {
        $g = ChurchWorldGraph::seed();

        app(AppointToSpiritualOffice::class)->execute(
            $g->pope,
            $g->bishopOffice,
            $g->princeBishop,
            Carbon::parse('1348-07-01')
        );

        $g->princeBishop->refresh();
        $this->assertTrue($g->princeBishop->currentTitleOwnerships()->exists());
        $this->assertTrue($g->princeBishop->currentSpiritualHolderships()->exists());
        $this->assertSame($g->capet->id, $g->princeBishop->dynasty_id);

        $this->assertSame(
            $g->princeBishop->id,
            TitleOwnership::query()->where('title_id', $g->princeBishopCounty->id)->where('is_current', true)->value('holder_character_id')
        );
        $this->assertSame(
            $g->princeBishop->id,
            SpiritualOfficeHoldership::query()->where('spiritual_office_id', $g->bishopOffice->id)->where('is_current', true)->value('holder_character_id')
        );
    }

    public function test_losing_secular_land_does_not_vacate_the_see(): void
    {
        $g = ChurchWorldGraph::seed();
        app(AppointToSpiritualOffice::class)->execute(
            $g->pope,
            $g->bishopOffice,
            $g->princeBishop,
            Carbon::parse('1348-07-01')
        );

        app(RevokeTitle::class)->execute($g->princeBishopCounty, Carbon::parse('1348-08-01'), $g->king);

        $this->assertFalse(
            TitleOwnership::query()->where('title_id', $g->princeBishopCounty->id)->where('is_current', true)->exists()
        );
        $this->assertSame(
            $g->princeBishop->id,
            SpiritualOfficeHoldership::query()->where('spiritual_office_id', $g->bishopOffice->id)->where('is_current', true)->value('holder_character_id')
        );
    }

    public function test_removing_church_office_does_not_strip_secular_title_or_dynasty(): void
    {
        $g = ChurchWorldGraph::seed();
        app(AppointToSpiritualOffice::class)->execute(
            $g->pope,
            $g->bishopOffice,
            $g->princeBishop,
            Carbon::parse('1348-07-01')
        );

        app(\App\Actions\Church\RemoveFromSpiritualOffice::class)->execute(
            $g->pope,
            $g->bishopOffice,
            Carbon::parse('1348-08-01')
        );

        $this->assertFalse(
            SpiritualOfficeHoldership::query()->where('spiritual_office_id', $g->bishopOffice->id)->where('is_current', true)->exists()
        );
        $this->assertSame(
            $g->princeBishop->id,
            TitleOwnership::query()->where('title_id', $g->princeBishopCounty->id)->where('is_current', true)->value('holder_character_id')
        );
        $this->assertSame($g->capet->id, $g->princeBishop->fresh()->dynasty_id);
        $this->assertSame($g->castle->fresh()->owner_character_id, $g->king->id);
    }

    public function test_title_rank_rejects_pope_and_bishop(): void
    {
        $g = ChurchWorldGraph::seed();

        $this->expectException(InvalidArgumentException::class);
        app(CreateTitle::class)->execute($g->world, 'The Papacy', 'pope');
    }

    public function test_spiritual_office_rejects_kingdom_rank(): void
    {
        $g = ChurchWorldGraph::seed();

        $this->expectException(InvalidArgumentException::class);
        app(CreateSpiritualOffice::class)->execute($g->world, 'fake-king', 'Fake King', TitleRank::KINGDOM);
    }

    public function test_papacy_is_not_a_title_and_is_unique_per_world(): void
    {
        $g = ChurchWorldGraph::seed();

        $this->assertSame(1, Papacy::query()->where('world_id', $g->world->id)->count());
        $this->assertFalse(Title::query()->where('world_id', $g->world->id)->where('rank', 'pope')->exists());
        $this->assertFalse(Title::query()->where('name', 'like', '%Papacy%')->exists());

        $this->expectException(RuntimeException::class);
        app(CreatePapacy::class)->execute($g->world, $g->faith, $g->papalSee, $g->papalOffice);
    }

    public function test_top_liege_walk_ignores_the_pope_unless_he_holds_a_secular_title_in_the_chain(): void
    {
        $g = ChurchWorldGraph::seed();

        $top = app(DetermineTopLiege::class)->execute($g->princeBishop);
        $this->assertSame($g->king->id, $top->id);
        $this->assertNotSame($g->pope->id, $top->id);

        $popeTop = app(DetermineTopLiege::class)->execute($g->pope);
        $this->assertSame($g->pope->id, $popeTop->id);
    }

    public function test_holding_owner_is_not_the_see_and_monastery_is_not_just_holding_type(): void
    {
        $g = ChurchWorldGraph::seed();

        $this->assertSame('castle', $g->castle->holding_type);
        $this->assertSame($g->king->id, $g->castle->owner_character_id);
        $this->assertNotNull($g->monastery->id);
        $this->assertSame($g->abbeyHolding->id, $g->monastery->holding_id);
        $this->assertSame('monastery', $g->abbeyHolding->holding_type);
        $this->assertNotSame($g->abbeyHolding->id, $g->monastery->id);
        $this->assertSame($g->benedictines->id, $g->monastery->religious_order_id);
    }

    public function test_holy_order_is_not_a_dynasty_or_realm(): void
    {
        $g = ChurchWorldGraph::seed();

        $this->assertSame('holy_orders', $g->templars->getTable());
        $this->assertNotSame('dynasties', $g->templars->getTable());
        $this->assertNotSame('realms', $g->templars->getTable());
        $this->assertSame($g->benedictines->id, $g->templars->religious_order_id);
        $this->assertSame('unsanctioned', $g->templars->sanction_status);
    }

    public function test_diocesan_chain_is_pope_archbishop_bishop_priest(): void
    {
        $g = ChurchWorldGraph::seed();
        $chain = app(DiocesanHierarchy::class)->chainFrom($g->parish);

        $this->assertSame([
            SeeKind::PARISH,
            SeeKind::DIOCESE,
            SeeKind::ARCHDIOCESE,
            SeeKind::PAPAL_SEE,
        ], array_map(fn ($see) => $see->see_type, $chain));
    }

    public function test_church_eligibility_can_block_secular_succession_without_touching_titles_table(): void
    {
        $g = ChurchWorldGraph::seed();
        $gate = app(ChurchEligibilityGate::class);

        $this->assertTrue($gate->isEligible($g->layCount, $g->kingdom));
        $this->assertFalse($gate->isEligible($g->bishop, $g->kingdom));
        $this->assertSame('major_orders', $gate->ineligibilityReason($g->bishop, $g->kingdom));
        $this->assertFalse($gate->isEligible($g->monk, $g->county));
        $this->assertSame('regular_vows', $gate->ineligibilityReason($g->monk, $g->county));
        $this->assertSame('titles', $g->kingdom->getTable());
        $this->assertSame('spiritual_offices', $g->bishopOffice->getTable());
    }

    public function test_see_tables_are_not_title_tables(): void
    {
        $g = ChurchWorldGraph::seed();

        $this->assertSame('sees', $g->diocese->getTable());
        $this->assertSame('titles', $g->kingdom->getTable());
        $this->assertSame('spiritual_offices', $g->bishopOffice->getTable());
        $this->assertSame('papacies', $g->papacy->getTable());
        $this->assertNotContains($g->diocese->see_type, TitleRank::all());
        $this->assertNotContains($g->bishopOffice->rank, TitleRank::all());
        $this->assertTrue(SpiritualOfficeRank::isSpiritual($g->bishopOffice->rank));
        $this->assertTrue(TitleRank::isSecular($g->kingdom->rank));
    }
}
