<?php

namespace Tests\Unit\Events;

use App\Domain\Events\DeterministicPicker;
use App\Domain\Events\EventCandidate;
use App\Domain\Events\EventCatalog;
use App\Domain\Events\EventConditionEvaluator;
use App\Domain\Events\EventDefinitionValidator;
use App\Domain\Events\EventWeightCalculator;
use App\Domain\Events\EventWorldView;
use PHPUnit\Framework\TestCase;

class EventEngineTest extends TestCase
{
    private function catalog(): EventCatalog
    {
        return EventCatalog::fromDirectory(dirname(__DIR__, 3).'/database/data/events');
    }

    private function view(array $overrides = []): EventWorldView
    {
        $base = new EventWorldView(
            1,
            '1348-06-24',
            'seed',
            'ordinary',
            1,
            3,
            ['global_corruption' => 2, 'plague_severity' => 0, 'despair' => 4, 'church_cohesion' => 82],
            [],
            [
                10 => ['id' => 10, 'name' => 'Salon', 'food_stores' => 40, 'population' => 200, 'despair' => 2, 'corruption' => 0, 'plague_intensity' => 0, 'owner_character_id' => 5],
            ],
            [
                5 => ['id' => 5, 'is_alive' => true, 'treasury' => 20, 'prestige' => 10, 'church_standing' => 8, 'residence_territory_id' => 10],
            ],
            [],
            [],
            [],
            [],
            [],
            [],
            [],
            [],
            [],
            [],
            [],
            [],
            []
        );

        foreach ($overrides as $prop => $value) {
            $base->{$prop} = $value;
        }

        return $base;
    }

    public function test_catalog_covers_requested_scopes_and_categories(): void
    {
        $catalog = $this->catalog();
        foreach (['character', 'dynasty', 'settlement', 'territory', 'realm', 'church_jurisdiction', 'monastery', 'army', 'war', 'cult', 'plague', 'apocalypse', 'world'] as $scope) {
            $this->assertContains($scope, $catalog->scopes());
        }
        foreach (['court', 'refugee', 'cult', 'miracle', 'demonic', 'church_politics'] as $category) {
            $this->assertContains($category, $catalog->categories());
        }
        $this->assertNotEmpty($catalog->definitions());
        $this->assertTrue($catalog->has('refugees_at_the_gate'));
    }

    public function test_definitions_validate(): void
    {
        $errors = (new EventDefinitionValidator())->validateCatalog($this->catalog());
        $this->assertSame([], $errors, implode("\n", $errors));
    }

    public function test_refugees_do_not_trigger_on_a_quiet_parish(): void
    {
        $eval = new EventConditionEvaluator();
        $def = $this->catalog()->definition('refugees_at_the_gate');
        $this->assertFalse($eval->matches($def['trigger'], $this->view(), 'territory', 10));
    }

    public function test_refugees_trigger_when_plague_is_in_the_land(): void
    {
        $eval = new EventConditionEvaluator();
        $view = $this->view();
        $view->territories[10]['plague_intensity'] = 2;
        $view->plagues[1] = ['id' => 1, 'status' => 'active', 'origin_territory_id' => 10];
        $def = $this->catalog()->definition('refugees_at_the_gate');
        $this->assertTrue($eval->matches($def['trigger'], $view, 'territory', 10));
    }

    public function test_refused_hook_raises_cult_weight(): void
    {
        $view = $this->view();
        $view->hooks[] = ['hook_key' => 'refugees_refused', 'scope_type' => 'territory', 'scope_id' => 10, 'intensity' => 1];
        $view->territories[10]['despair'] = 12;
        $weights = new EventWeightCalculator(new EventConditionEvaluator());
        $base = $weights->compute($this->catalog()->definition('cult_finds_the_refused'), $this->view(), 'territory', 10);
        $raised = $weights->compute($this->catalog()->definition('cult_finds_the_refused'), $view, 'territory', 10);
        $this->assertGreaterThan($base, $raised);
    }

    public function test_picker_is_deterministic(): void
    {
        $candidates = [
            new EventCandidate(['key' => 'a'], 'territory', 1, 10, 1),
            new EventCandidate(['key' => 'b'], 'territory', 2, 40, 1),
        ];
        $picker = new DeterministicPicker();
        $first = $picker->pick($candidates, 'seed', '1348-06-24', 1);
        $second = $picker->pick($candidates, 'seed', '1348-06-24', 1);
        $this->assertSame($first[0]->key(), $second[0]->key());
        $this->assertSame($first[0]->scopeId, $second[0]->scopeId);
    }
}
