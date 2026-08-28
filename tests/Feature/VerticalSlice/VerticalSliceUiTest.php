<?php

namespace Tests\Feature\VerticalSlice;

use App\Actions\Campaign\SeedVerticalSlice;
use App\Domain\Enums\GameEventStatus;
use App\Models\Army;
use App\Models\GameEvent;
use App\Models\Territory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VerticalSliceUiTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_and_every_play_page_renders(): void
    {
        $slice = app(SeedVerticalSlice::class)->execute();
        $salon = Territory::query()->where('world_id', $slice->world->id)->where('key', 'salon')->firstOrFail();

        $this->get('/')->assertRedirect('/login');

        $this->actingAs($slice->user);

        $pages = [
            '/dashboard' => 'County of Salon',
            '/character' => 'Raimond',
            '/dynasty' => 'Adhémar',
            '/titles' => 'County of Salon',
            '/realm' => 'Gui',
            '/map' => 'Salon-de-Provence',
            '/church' => 'Diocese of Aix',
            '/spiritual' => 'Faith and corruption',
            '/plague' => 'Plague',
            '/army' => 'Host of Miramas',
            '/apocalypse' => 'Apocalypse',
            '/events' => 'Plague in Aix',
        ];

        foreach ($pages as $path => $needle) {
            $this->get($path)->assertOk()->assertSee($needle, false);
        }

        $this->get('/dashboard')
            ->assertOk()
            ->assertSee('Campaign goals', false)
            ->assertSee('No campaign goals yet.', false);

        $this->get('/settlements/'.$salon->id)->assertOk()->assertSee('Salon-de-Provence', false);
    }

    public function test_advance_day_and_raise_army_from_the_ui(): void
    {
        $slice = app(SeedVerticalSlice::class)->execute();
        $this->actingAs($slice->user);

        $this->post('/time/advance')->assertRedirect('/dashboard');
        $this->get('/events')
            ->assertSee('awaiting decision', false)
            ->assertDontSee('awaiting_decision', false);

        $this->post('/army/raise', ['strength' => 80])->assertRedirect('/army');
        $this->get('/army')->assertSee('Levy of Salon', false);
    }

    public function test_invalid_event_option_redirects_with_error_not_500(): void
    {
        $slice = app(SeedVerticalSlice::class)->execute();
        $this->actingAs($slice->user);

        $this->post('/time/advance')->assertRedirect('/dashboard');

        $event = GameEvent::query()
            ->where('world_id', $slice->world->id)
            ->where('status', GameEventStatus::AWAITING_DECISION)
            ->firstOrFail();

        $this->post('/events/'.$event->id.'/resolve', ['option' => 'not_a_real_option'])
            ->assertRedirect('/events')
            ->assertSessionHas('error');

        $this->assertSame(GameEventStatus::AWAITING_DECISION, $event->fresh()->status);

        $validOption = (string) array_key_first($event->options ?? []);
        $optionLabel = (string) ($event->options[$validOption] ?? $validOption);
        $this->post('/events/'.$event->id.'/resolve', ['option' => $validOption])
            ->assertRedirect('/events')
            ->assertSessionHas('status', 'Decision recorded: '.$optionLabel);

        $event->refresh();
        $this->assertSame(GameEventStatus::RESOLVED, $event->status);
        $this->assertSame($validOption, $event->chosen_option);

        $this->get('/events')
            ->assertSee('Chosen: '.$optionLabel, false)
            ->assertDontSee('Chosen: '.$validOption, false)
            ->assertDontSee($event->event_key, false);
    }

    public function test_over_levy_redirects_with_error_not_500(): void
    {
        $slice = app(SeedVerticalSlice::class)->execute();
        $this->actingAs($slice->user);
        $salon = Territory::query()->where('world_id', $slice->world->id)->where('key', 'salon')->firstOrFail();
        $tooMany = ((int) $salon->levy_available) + 1;

        $this->post('/army/raise', ['strength' => $tooMany])
            ->assertRedirect('/army')
            ->assertSessionHas('error');
    }

    public function test_invalid_march_redirects_with_error_not_500(): void
    {
        $slice = app(SeedVerticalSlice::class)->execute();
        $this->actingAs($slice->user);

        $this->post('/army/raise', ['strength' => 80])->assertRedirect('/army');

        $army = Army::query()->where('world_id', $slice->world->id)->where('owner_character_id', $slice->ruler->id)->firstOrFail();
        $miramas = Territory::query()->where('world_id', $slice->world->id)->where('key', 'miramas')->firstOrFail();

        $this->post('/army/'.$army->id.'/move', ['territory_id' => $miramas->id])
            ->assertRedirect('/army')
            ->assertSessionHas('error');
    }

    public function test_raise_without_residence_redirects_with_error_not_500(): void
    {
        $slice = app(SeedVerticalSlice::class)->execute();
        $this->actingAs($slice->user);

        $slice->ruler->residence_territory_id = null;
        $slice->ruler->save();

        $this->post('/army/raise', ['strength' => 80])
            ->assertRedirect('/army')
            ->assertSessionHas('error');
    }
}
