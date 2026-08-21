<?php

namespace Tests\Feature\Campaign1347;

use App\Actions\Campaign\RunCampaignPulse;
use App\Actions\Campaign\SeedEuropa1347;
use App\Actions\Time\AdvanceWorldCalendar;
use App\Domain\Campaign\Historical\HistoricalValidator;
use App\Domain\Campaign\Opening\OpeningTriggerCatalog;
use App\Domain\Campaign\Opening\WeightedTriggerEvaluator;
use App\Domain\Campaign\Opening\WorldConditionSnapshot;
use App\Domain\Campaign\PlayerArchetype;
use App\Domain\Enums\ArmyKind;
use App\Domain\Enums\SpiritualOfficeRank;
use App\Domain\Enums\TitleRank;
use App\Models\Army;
use App\Models\CampaignGoalProgress;
use App\Models\CampaignState;
use App\Models\Character;
use App\Models\Cult;
use App\Actions\Campaign\ResolveGameEvent;
use App\Domain\Enums\GameEventStatus;
use App\Models\GameEvent;
use App\Models\HolyOrder;
use App\Models\Papacy;
use App\Models\Realm;
use App\Models\Territory;
use App\Models\TerritoryPlagueState;
use App\Models\Title;
use App\Models\TitleOwnership;
use App\Validation\WorldIntegrityValidator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class Europa1347CampaignTest extends TestCase
{
    use RefreshDatabase;

    public function test_seed_creates_a_functioning_1347_europe(): void
    {
        $ctx = app(SeedEuropa1347::class)->execute(PlayerArchetype::KING, 'king-a@diesirae.test');

        $this->assertSame('europa-1347', $ctx->world->slug);
        $this->assertSame('1347-10-01', $ctx->world->start_date->toDateString());
        $this->assertGreaterThanOrEqual(20, $ctx->territories->count());
        $this->assertGreaterThanOrEqual(8, $ctx->world->realms()->count());
        $this->assertSame('occupied', Papacy::query()->where('world_id', $ctx->world->id)->value('status'));
        $this->assertSame(1, TerritoryPlagueState::query()->where('world_id', $ctx->world->id)->count());
        $this->assertSame('messina', Territory::query()->find(
            TerritoryPlagueState::query()->where('world_id', $ctx->world->id)->value('territory_id')
        )->key);
        $this->assertFalse(Cult::query()->where('world_id', $ctx->world->id)->where('revealed', true)->exists());
        $this->assertFalse(Army::query()->where('world_id', $ctx->world->id)->where('kind', ArmyKind::DEMONIC)->exists());

        $report = (new WorldIntegrityValidator())->validate($ctx->world);
        $this->assertTrue($report->passed(), json_encode($report->toArray()));
    }

    public function test_historical_validator_accepts_the_seed(): void
    {
        $ctx = app(SeedEuropa1347::class)->execute(PlayerArchetype::KING, 'king-b@diesirae.test');
        $report = app(HistoricalValidator::class)->validate($ctx->world);
        $this->assertTrue($report->passed(), json_encode($report->toArray()));
    }

    public function test_all_listed_archetypes_exist_and_match_authority_rules(): void
    {
        $ctx = app(SeedEuropa1347::class)->execute(PlayerArchetype::COUNT, 'count-a@diesirae.test');
        $world = $ctx->world;

        $philip = Character::query()->where('key', 'philip_vi')->firstOrFail();
        $jean = Character::query()->where('key', 'jean_normandy')->firstOrFail();
        $raimond = Character::query()->where('key', 'raimond_adhemar')->firstOrFail();
        $gui = Character::query()->where('key', 'gui_pelissanne')->firstOrFail();
        $jacques = Character::query()->where('key', 'jacques_aix')->firstOrFail();
        $walram = Character::query()->where('key', 'walram_cologne')->firstOrFail();
        $dieudonne = Character::query()->where('key', 'dieudonne_gozon')->firstOrFail();

        $this->assertSame(TitleRank::KINGDOM, Title::query()->where('key', 'kingdom_france')->value('rank'));
        $this->assertSame($philip->id, TitleOwnership::query()->where('title_id', Title::query()->where('key', 'kingdom_france')->value('id'))->where('is_current', true)->value('holder_character_id'));
        $this->assertSame($jean->id, TitleOwnership::query()->where('title_id', Title::query()->where('key', 'duchy_normandy')->value('id'))->where('is_current', true)->value('holder_character_id'));
        $this->assertSame($raimond->id, TitleOwnership::query()->where('title_id', Title::query()->where('key', 'county_salon')->value('id'))->where('is_current', true)->value('holder_character_id'));
        $this->assertSame($gui->id, TitleOwnership::query()->where('title_id', Title::query()->where('key', 'barony_pelissanne')->value('id'))->where('is_current', true)->value('holder_character_id'));

        $this->assertSame(0, $jacques->currentTitleOwnerships()->count());
        $this->assertSame(SpiritualOfficeRank::ARCHBISHOP, $jacques->currentSpiritualHolderships()->first()->office->rank);

        $this->assertTrue($walram->currentTitleOwnerships()->exists());
        $this->assertSame(SpiritualOfficeRank::ARCHBISHOP, $walram->currentSpiritualHolderships()->first()->office->rank);

        $this->assertSame(0, $dieudonne->currentTitleOwnerships()->count());
        $this->assertTrue(HolyOrder::query()->where('world_id', $world->id)->where('key', 'knights_hospitaller')->exists());
        $this->assertFalse(Realm::query()->where('world_id', $world->id)->where('key', 'like', '%hospital%')->exists());
        $this->assertSame($ctx->player->id, $raimond->id);
    }

    public function test_trigger_catalog_covers_the_opening_families_and_does_not_script_one_order(): void
    {
        $catalog = app(OpeningTriggerCatalog::class);
        foreach ([
            'plague_rumor', 'merchant_report', 'clergy_warning', 'mass_death', 'disrupted_harvest',
            'refugee_movement', 'accusation_of_sin', 'political_opportunism', 'strange_omen',
            'disappearing_travelers', 'unexplained_violence', 'first_manifestation',
        ] as $family) {
            $this->assertContains($family, $catalog->families());
        }

        $early = new WorldConditionSnapshot();
        $early->monthsElapsed = 0;
        $early->archetype = 'king';
        $early->hasTrade = true;
        $early->hasWar = true;
        $early->plagueExists = true;
        $early->tradeToPlague = ['genoa'];
        $early->hasVassal = true;

        $late = clone $early;
        $late->monthsElapsed = 20;
        $late->massDeathFired = true;
        $late->cultActivity = 3;
        $late->despair = 10;
        $late->localPlagueIntensity = 3;
        $late->plagueTerritories = ['messina'];

        $eval = app(WeightedTriggerEvaluator::class);
        $earlyKeys = array_column($eval->eligible($early, [], [], 'king'), 'family');
        $lateKeys = array_column($eval->eligible($late, [], [], 'king'), 'family');

        $this->assertContains('plague_rumor', $earlyKeys);
        $this->assertContains('feudal_levy_call', $earlyKeys);
        $this->assertNotContains('first_manifestation', $earlyKeys);
        $this->assertContains('first_manifestation', $lateKeys);
        $this->assertContains('mass_death', $lateKeys);
    }

    public function test_different_seeds_do_not_guarantee_the_same_event_order(): void
    {
        $a = app(SeedEuropa1347::class)->execute(PlayerArchetype::KING, 'king-seed-a@diesirae.test', null, 11, 'europa-1347-a');
        $b = app(SeedEuropa1347::class)->execute(PlayerArchetype::KING, 'king-seed-b@diesirae.test', null, 99, 'europa-1347-b');

        $seqA = $this->pulseFamilies($a->world, 24);
        $seqB = $this->pulseFamilies($b->world, 24);

        $this->assertNotSame([], $seqA);
        $this->assertNotSame([], $seqB);
        $this->assertNotSame($seqA, $seqB);
    }

    public function test_opening_window_can_produce_required_opportunity_types(): void
    {
        $ctx = app(SeedEuropa1347::class)->execute(PlayerArchetype::KING, 'king-open@diesirae.test', null, 13471001);
        $this->pulseFamilies($ctx->world, 78);
        $families = GameEvent::query()->where('world_id', $ctx->world->id)->whereNotNull('catalog_family')->pluck('catalog_family')->unique()->all();
        $this->assertGreaterThanOrEqual(6, count($families), json_encode($families));
        $this->assertTrue(
            CampaignState::query()->where('world_id', $ctx->world->id)->value('pulse_count') >= 20
        );
    }

    public function test_player_apocalypse_page_hides_meters_and_admin_inspect_does_not(): void
    {
        $ctx = app(SeedEuropa1347::class)->execute(PlayerArchetype::KING, 'king-fog@diesirae.test');
        $this->actingAs($ctx->user);
        $this->get('/apocalypse')
            ->assertOk()
            ->assertSee('The year keeps its shape', false)
            ->assertDontSee('demonic_manifestation', false)
            ->assertDontSee('phase_key', false);
        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Kingdom of France', false)
            ->assertSee('Campaign goals', false)
            ->assertSee('Preserve the dynasty', false)
            ->assertSee('available', false)
            ->assertDontSee('preserve_dynasty', false)
            ->assertDontSee('phase_key', false);

        $this->get('/dev/inspect/apocalypse/'.$ctx->world->id)
            ->assertOk()
            ->assertSee('ordinary', false);
    }

    public function test_unrevealed_cults_are_hidden_from_the_spiritual_page(): void
    {
        $ctx = app(SeedEuropa1347::class)->execute(PlayerArchetype::KING, 'king-cult@diesirae.test');
        $this->actingAs($ctx->user);
        $this->get('/spiritual')->assertOk()->assertDontSee('Brothers of the Open Grave', false);
    }

    public function test_calendar_advance_runs_the_campaign_pulse(): void
    {
        $ctx = app(SeedEuropa1347::class)->execute(PlayerArchetype::KING, 'king-cal@diesirae.test', null, 7);
        $before = GameEvent::query()->where('world_id', $ctx->world->id)->count();
        app(AdvanceWorldCalendar::class)->execute($ctx->world, 14);
        $this->assertGreaterThan(
            0,
            (int) CampaignState::query()->where('world_id', $ctx->world->id)->value('pulse_count')
        );
        $this->assertGreaterThanOrEqual($before, GameEvent::query()->where('world_id', $ctx->world->id)->count());
    }

    public function test_holy_order_and_bishop_starts_are_assignable(): void
    {
        $bishop = app(SeedEuropa1347::class)->execute(PlayerArchetype::BISHOP, 'bishop-a@diesirae.test', null, 3, 'europa-1347-bishop');
        $this->assertSame('jacques_aix', $bishop->player->key);
        $this->assertSame(0, $bishop->player->currentTitleOwnerships()->count());

        $order = app(SeedEuropa1347::class)->execute(PlayerArchetype::HOLY_ORDER, 'hospital-a@diesirae.test', null, 4, 'europa-1347-hospital');
        $this->assertSame('dieudonne_gozon', $order->player->key);
        $this->actingAs($order->user);
        $this->get('/dashboard')->assertOk()->assertSee('Dieudonné', false);
        $this->get('/army')->assertOk();
    }

    public function test_slice_world_is_not_the_1347_campaign(): void
    {
        $campaign = app(SeedEuropa1347::class)->execute(PlayerArchetype::KING, 'king-iso@diesirae.test');
        $this->assertNotSame('provence-1347', $campaign->world->slug);
        $this->assertSame(0, GameEvent::query()->where('world_id', $campaign->world->id)->where('event_key', 'plague_appears')->count());
    }

    public function test_campaign_choice_that_nudges_a_goal_to_full_marks_it_completed(): void
    {
        $ctx = app(SeedEuropa1347::class)->execute(PlayerArchetype::KING, 'king-goal@diesirae.test', null, 5, 'europa-1347-goal');

        $goal = CampaignGoalProgress::query()
            ->where('world_id', $ctx->world->id)
            ->where('character_id', $ctx->player->id)
            ->where('goal_key', 'regional_hegemon')
            ->firstOrFail();
        $goal->progress = 90;
        $goal->status = 'available';
        $goal->save();

        $other = CampaignGoalProgress::query()
            ->where('world_id', $ctx->world->id)
            ->where('character_id', $ctx->player->id)
            ->where('goal_key', 'preserve_dynasty')
            ->firstOrFail();
        $otherStatus = $other->status;
        $otherProgress = $other->progress;

        $place = Territory::query()->where('world_id', $ctx->world->id)->where('key', 'paris')->firstOrFail();
        $event = GameEvent::query()->create([
            'world_id' => $ctx->world->id,
            'event_key' => 'political_opportunism:paris:test',
            'catalog_family' => 'political_opportunism',
            'title' => 'A vassal smells weakness',
            'body' => 'Test political opportunism.',
            'status' => GameEventStatus::AWAITING_DECISION,
            'due_on' => $ctx->world->current_date->toDateString(),
            'options' => [
                'demand_contract' => 'Demand the contract',
                'buy_loyalty' => 'Buy loyalty with coin',
                'wait' => 'Wait out the season',
            ],
            'payload' => [
                'family' => 'political_opportunism',
                'territory_key' => $place->key,
            ],
            'audience_character_id' => $ctx->player->id,
            'territory_id' => $place->id,
        ]);

        app(ResolveGameEvent::class)->execute($event, 'demand_contract');

        $goal->refresh();
        $this->assertSame(100, (int) $goal->progress);
        $this->assertSame('completed', $goal->status);

        $other->refresh();
        $this->assertSame($otherStatus, $other->status);
        $this->assertSame($otherProgress, (int) $other->progress);
    }

    /**
     * @return list<string>
     */
    private function pulseFamilies($world, int $pulses): array
    {
        $pulse = app(RunCampaignPulse::class);
        $families = [];
        for ($i = 0; $i < $pulses; $i++) {
            $world->current_date = $world->current_date->copy()->addDays(14);
            $world->save();
            $result = $pulse->execute($world);
            foreach ($result['fired'] ?? [] as $family) {
                $families[] = $family;
            }

            $pending = GameEvent::query()
                ->where('world_id', $world->id)
                ->where('status', 'awaiting_decision')
                ->get();

            foreach ($pending as $event) {
                $options = $event->options ?? [];
                $choice = array_key_first($options);

                if ($choice !== null) {
                    app(ResolveGameEvent::class)->execute($event, $choice);
                }
            }
        }

        return $families;
    }
}
