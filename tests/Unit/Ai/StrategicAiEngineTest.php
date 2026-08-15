<?php

namespace Tests\Unit\Ai;

use App\Domain\Ai\Enums\ActorType;
use App\Domain\Ai\Reasoners\BishopReasoner;
use App\Domain\Ai\Reasoners\KingReasoner;
use App\Domain\Ai\State\MemoryEntry;
use PHPUnit\Framework\TestCase;

final class StrategicAiEngineTest extends TestCase
{
    public function test_king_and_bishop_choose_different_answers_to_the_same_incursion(): void
    {
        $engine = StrategicAiHarness::engine();
        $world = StrategicAiHarness::incursion();

        $king = $engine->consider(StrategicAiHarness::actor('louis', ActorType::KING), $world);
        $bishop = $engine->consider(StrategicAiHarness::actor('hugues', ActorType::BISHOP), $world);

        $this->assertNotSame($king->actionKey, $bishop->actionKey);
        $this->assertContains($king->actionKey, ['military_cleansing', 'call_bannermen', 'fortify']);
        $this->assertContains($bishop->actionKey, ['exorcism', 'consecrate_land', 'call_for_relic']);
        $this->assertSame(KingReasoner::class, $king->trace->reasoner);
        $this->assertSame(BishopReasoner::class, $bishop->trace->reasoner);
        $this->assertStringContainsString('KingReasoner', $king->trace->explain());
    }

    public function test_ambitious_and_cautious_dukes_split_on_succession(): void
    {
        $engine = StrategicAiHarness::engine();
        $world = StrategicAiHarness::succession();

        $ambitious = StrategicAiHarness::actor('raymond', ActorType::DUKE, [
            'ambitious' => 95,
            'cautious' => 5,
        ]);
        $cautious = StrategicAiHarness::actor('bertrand', ActorType::DUKE, [
            'ambitious' => 5,
            'cautious' => 95,
        ]);

        $a = $engine->consider($ambitious, $world);
        $c = $engine->consider($cautious, $world);

        $this->assertSame('press_claim', $a->actionKey);
        $this->assertNotSame($a->actionKey, $c->actionKey);
        $this->assertContains($c->actionKey, ['fortify', 'petition_liege', 'secure_succession', 'wait']);
    }

    public function test_monastery_and_realm_split_on_famine(): void
    {
        $engine = StrategicAiHarness::engine();
        $world = StrategicAiHarness::famine();

        $abbey = $engine->consider(StrategicAiHarness::actor('silvacane', ActorType::MONASTERY), $world);
        $realm = $engine->consider(StrategicAiHarness::actor('provence', ActorType::REALM), $world);

        $this->assertSame('open_granary', $abbey->actionKey);
        $this->assertNotSame($abbey->actionKey, $realm->actionKey);
        $this->assertContains($realm->actionKey, ['seize_grain', 'close_borders', 'fortify', 'quarantine']);
    }

    public function test_pope_will_not_pursue_a_dynasty(): void
    {
        $engine = StrategicAiHarness::engine();
        $world = StrategicAiHarness::succession();
        $world->hasPapalOffice = true;

        $pope = $engine->consider(StrategicAiHarness::actor('clement', ActorType::POPE), $world);
        $king = $engine->consider(StrategicAiHarness::actor('philip', ActorType::KING), $world);

        $this->assertNotContains($pope->actionKey, ['press_claim', 'arrange_heir_marriage', 'secure_succession']);
        $this->assertContains($king->actionKey, ['secure_succession', 'press_claim', 'arrange_heir_marriage', 'pacify_vassals']);
        $rejected = array_map(fn ($r) => $r->key, $pope->trace->rejected);
        $this->assertNotContains('press_claim', array_map(fn ($s) => $s->key, $pope->trace->considered));
    }

    public function test_prince_bishop_changes_mind_when_the_hat_changes(): void
    {
        $engine = StrategicAiHarness::engine();
        $world = StrategicAiHarness::incursion();

        $countHat = StrategicAiHarness::actor('raimond', ActorType::COUNT);
        $bishopHat = StrategicAiHarness::actor('raimond', ActorType::BISHOP);

        $asCount = $engine->consider($countHat, $world);
        $asBishop = $engine->consider($bishopHat, $world);

        $this->assertNotSame($asCount->actionKey, $asBishop->actionKey);
        $this->assertNotSame('exorcism', $asCount->actionKey);
        $this->assertNotSame('raise_levy', $asBishop->actionKey);
    }

    public function test_cooldown_rejects_repeat_and_picks_another_act(): void
    {
        $engine = StrategicAiHarness::engine();
        $world = StrategicAiHarness::incursion();
        $king = StrategicAiHarness::actor('louis', ActorType::KING);
        $first = $engine->consider($king, $world);
        $king->cooldown($first->actionKey, '1348-08-01');

        $second = $engine->consider($king, $world);

        $this->assertNotSame($first->actionKey, $second->actionKey);
        $rejected = array_column(array_map(fn ($r) => $r->toArray(), $second->trace->rejected), 'reason', 'key');
        $this->assertArrayHasKey($first->actionKey, $rejected);
        $this->assertStringContainsString('cooldown', $rejected[$first->actionKey]);
    }

    public function test_merchant_and_count_split_on_plague(): void
    {
        $engine = StrategicAiHarness::engine();
        $world = StrategicAiHarness::plague();

        $merchant = $engine->consider(StrategicAiHarness::actor('jacopo', ActorType::MERCHANT, ['greedy' => 95, 'cautious' => 20]), $world);
        $count = $engine->consider(StrategicAiHarness::actor('salon', ActorType::COUNT), $world);

        $this->assertSame('relocate_goods', $merchant->actionKey);
        $this->assertNotSame($merchant->actionKey, $count->actionKey);
        $this->assertContains($count->actionKey, ['quarantine', 'open_granary', 'fortify', 'petition_liege']);
    }

    public function test_cult_hides_while_bishop_exposes(): void
    {
        $engine = StrategicAiHarness::engine();
        $world = StrategicAiHarness::cultDiscovery();

        $cult = $engine->consider(StrategicAiHarness::actor('nave', ActorType::CULT_LEADER), $world);
        $bishop = $engine->consider(StrategicAiHarness::actor('hugues', ActorType::BISHOP), $world);

        $this->assertSame('conceal_cell', $cult->actionKey);
        $this->assertContains($bishop->actionKey, ['expose_cult', 'investigate_heresy', 'preach', 'exorcism']);
    }

    public function test_demon_and_holy_order_want_opposite_things_at_a_breach(): void
    {
        $engine = StrategicAiHarness::engine();
        $world = StrategicAiHarness::incursion();

        $demon = $engine->consider(StrategicAiHarness::actor('legion', ActorType::DEMON_COMMANDER), $world);
        $order = $engine->consider(StrategicAiHarness::actor('master', ActorType::HOLY_ORDER_LEADER), $world);

        $this->assertContains($demon->actionKey, ['expand_breach', 'harvest_fear', 'corrupt_garrison', 'accelerate_rite']);
        $this->assertContains($order->actionKey, ['march_on_breach', 'military_cleansing', 'hold_the_line']);
        $this->assertNotSame($demon->actionKey, $order->actionKey);
    }

    public function test_heretic_preaches_failure_where_bishop_investigates(): void
    {
        $engine = StrategicAiHarness::engine();
        $world = StrategicAiHarness::heresy();

        $heretic = $engine->consider(StrategicAiHarness::actor('fra', ActorType::HERETIC_LEADER), $world);
        $bishop = $engine->consider(StrategicAiHarness::actor('hugues', ActorType::BISHOP), $world);

        $this->assertSame('preach_against_church', $heretic->actionKey);
        $this->assertContains($bishop->actionKey, ['investigate_heresy', 'preach', 'expose_cult']);
    }

    public function test_identical_inputs_are_deterministic(): void
    {
        $engine = StrategicAiHarness::engine();
        $world = StrategicAiHarness::incursion();
        $a = $engine->consider(StrategicAiHarness::actor('louis', ActorType::KING), $world);
        $b = $engine->consider(StrategicAiHarness::actor('louis', ActorType::KING), $world);

        $this->assertSame($a->actionKey, $b->actionKey);
        $this->assertEquals($a->score, $b->score);
        $this->assertSame($a->trace->explain(), $b->trace->explain());
    }

    public function test_debug_trace_lists_scores_rejections_and_modifiers(): void
    {
        $engine = StrategicAiHarness::engine();
        $world = StrategicAiHarness::incursion();
        $king = StrategicAiHarness::actor('louis', ActorType::KING);
        $king->remember(new MemoryEntry('lost-levy', 'failure', 80, 'raise_levy'));

        $decision = $engine->consider($king, $world);
        $text = $decision->trace->explain();

        $this->assertNotEmpty($decision->trace->considered);
        $this->assertNotNull($decision->trace->chosen);
        $this->assertStringContainsString('chosen:', $text);
        $this->assertStringContainsString('considered', $text);
        $this->assertNotEmpty($decision->trace->majorModifiers);
        foreach ($decision->trace->considered as $row) {
            $this->assertIsFloat($row->final);
        }
    }

    public function test_papacy_without_a_pope_cannot_issue_a_bull(): void
    {
        $engine = StrategicAiHarness::engine();
        $world = StrategicAiHarness::incursion();
        $world->hasPapalOffice = false;

        $curia = $engine->consider(StrategicAiHarness::actor('curia', ActorType::PAPACY), $world);
        $rejected = array_map(fn ($r) => $r->key, $curia->trace->rejected);

        $this->assertContains('issue_bull', $rejected);
        $this->assertNotSame('issue_bull', $curia->actionKey);
    }

    public function test_every_actor_type_has_a_reasoner_and_can_decide(): void
    {
        $engine = StrategicAiHarness::engine();
        $world = StrategicAiHarness::incursion();

        foreach (ActorType::all() as $type) {
            $decision = $engine->consider(StrategicAiHarness::actor($type, $type), $world);
            $this->assertNotSame('', $decision->actionKey, $type.' produced no act');
            $this->assertNotEmpty($decision->trace->considered, $type.' considered nothing');
        }
    }
}
