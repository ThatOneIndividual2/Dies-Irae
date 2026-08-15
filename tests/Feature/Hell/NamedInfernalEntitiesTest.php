<?php

namespace Tests\Feature\Hell;

use App\Actions\Campaign\SeedVerticalSlice;
use App\Actions\Hell\RevealNamedDemonKnowledge;
use App\Domain\Hell\Enums\KnowledgeFacet;
use App\Domain\Hell\Enums\KnowledgeSource;
use App\Domain\Hell\Enums\NamedDemonStatus;
use App\Models\NamedDemon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NamedInfernalEntitiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_slice_seeds_three_latent_fixture_demons_without_true_names_on_play(): void
    {
        $slice = app(SeedVerticalSlice::class)->execute();
        $demons = NamedDemon::query()->where('world_id', $slice->world->id)->orderBy('catalog_key')->get();

        $this->assertCount(3, $demons);
        $this->assertEqualsCanonicalizing(
            ['apollyon_the_unmaker', 'mammon_the_gilded', 'the_whisper_in_the_nave'],
            $demons->pluck('catalog_key')->all()
        );
        $this->assertTrue($demons->every(fn (NamedDemon $d) => $d->status === NamedDemonStatus::LATENT));
        $this->assertSame('corrupter', $demons->firstWhere('catalog_key', 'mammon_the_gilded')->strategy);
        $this->assertSame('cult_organizer', $demons->firstWhere('catalog_key', 'the_whisper_in_the_nave')->strategy);
        $this->assertSame('military_invader', $demons->firstWhere('catalog_key', 'apollyon_the_unmaker')->strategy);

        $this->actingAs($slice->user);
        $this->get('/spiritual')
            ->assertOk()
            ->assertSee('the gilded moth', false)
            ->assertSee('a draft in the choir', false)
            ->assertSee('the locust captain', false)
            ->assertDontSee('Mammon', false)
            ->assertDontSee('Apollyon', false)
            ->assertDontSee('Treasurer of the Outer Court', false);
    }

    public function test_manuscript_can_reveal_true_identity_to_the_ruler(): void
    {
        $slice = app(SeedVerticalSlice::class)->execute();
        $mammon = NamedDemon::query()
            ->where('world_id', $slice->world->id)
            ->where('catalog_key', 'mammon_the_gilded')
            ->firstOrFail();

        app(RevealNamedDemonKnowledge::class)->execute(
            $mammon,
            'character:'.$slice->ruler->id,
            KnowledgeFacet::TRUE_IDENTITY,
            KnowledgeSource::MANUSCRIPT,
            $slice->world->current_date->toDateString()
        );

        $this->actingAs($slice->user);
        $this->get('/spiritual')->assertOk()->assertSee('Mammon', false);
    }

    public function test_local_inspect_shows_hidden_true_state(): void
    {
        $slice = app(SeedVerticalSlice::class)->execute();

        $this->get('/dev/inspect/named/'.$slice->world->id)
            ->assertOk()
            ->assertSee('Mammon', false)
            ->assertSee('A hunger that wears a merchant', false)
            ->assertSee('apollyon_the_unmaker', false);
    }
}
