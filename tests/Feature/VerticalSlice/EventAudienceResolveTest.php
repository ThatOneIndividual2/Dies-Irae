<?php

namespace Tests\Feature\VerticalSlice;

use App\Actions\Campaign\SeedVerticalSlice;
use App\Actions\Control\AssignCharacterControl;
use App\Domain\Enums\GameEventStatus;
use App\Models\GameEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class EventAudienceResolveTest extends TestCase
{
    use RefreshDatabase;

    public function test_same_world_non_audience_cannot_resolve_event(): void
    {
        $slice = app(SeedVerticalSlice::class)->execute('audience-ruler@diesirae.test');

        $otherUser = User::query()->create([
            'name' => 'Other Player',
            'email' => 'audience-other@diesirae.test',
            'password' => Hash::make('secret'),
            'world_id' => $slice->world->id,
        ]);
        app(AssignCharacterControl::class)->execute(
            $otherUser,
            $slice->vassal,
            $slice->world->current_date
        );

        $event = GameEvent::query()->create([
            'world_id' => $slice->world->id,
            'event_key' => 'audience-only-test',
            'title' => 'Audience Only Decision',
            'body' => 'Only the intended audience may resolve this.',
            'status' => GameEventStatus::AWAITING_DECISION,
            'due_on' => $slice->world->current_date->toDateString(),
            'options' => [
                'accept' => 'Accept',
                'refuse' => 'Refuse',
            ],
            'payload' => [],
            'audience_character_id' => $slice->ruler->id,
        ]);

        $this->actingAs($otherUser->fresh());

        $this->get('/events')
            ->assertOk()
            ->assertDontSee('Audience Only Decision', false);

        $this->post('/events/'.$event->id.'/resolve', ['option' => 'accept'])
            ->assertForbidden();

        $this->assertSame(GameEventStatus::AWAITING_DECISION, $event->fresh()->status);

        $this->actingAs($slice->user);

        $this->get('/events')
            ->assertOk()
            ->assertSee('Audience Only Decision', false);

        $this->post('/events/'.$event->id.'/resolve', ['option' => 'accept'])
            ->assertRedirect('/events');

        $this->assertSame(GameEventStatus::RESOLVED, $event->fresh()->status);
        $this->assertSame('accept', $event->fresh()->chosen_option);
    }
}
