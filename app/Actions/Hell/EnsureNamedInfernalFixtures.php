<?php

namespace App\Actions\Hell;

use App\Domain\Hell\Enums\NamedDemonStatus;
use App\Domain\Hell\Enums\ObjectiveStatus;
use App\Domain\Hell\TaxonomyCatalog;
use App\Models\Cult;
use App\Models\DemonicFaction;
use App\Models\NamedDemon;
use App\Models\World;
use Illuminate\Support\Facades\DB;

/**
 * Idempotent. Does not reset worlds. Incursions still work with zero named rows.
 */
final class EnsureNamedInfernalFixtures
{
    public const FIXTURES = [
        'mammon_the_gilded' => 'court_of_the_gilded_moth',
        'the_whisper_in_the_nave' => 'nave_whisperers',
        'apollyon_the_unmaker' => 'locust_host',
    ];

    public function __construct(private TaxonomyCatalog $catalog)
    {
    }

    public function execute(World $world): void
    {
        DB::transaction(function () use ($world) {
            foreach (self::FIXTURES as $catalogKey => $factionKey) {
                $this->ensureFaction($world, $factionKey);
                $this->ensureNamed($world, $catalogKey, $factionKey);
            }

            $whisper = NamedDemon::query()
                ->where('world_id', $world->id)
                ->where('catalog_key', 'the_whisper_in_the_nave')
                ->first();
            if ($whisper) {
                Cult::query()
                    ->where('world_id', $world->id)
                    ->where('key', 'brothers_open_grave')
                    ->update(['patron_named_key' => $whisper->catalog_key]);
            }
        });
    }

    private function ensureFaction(World $world, string $factionKey): DemonicFaction
    {
        $existing = DemonicFaction::query()
            ->where('world_id', $world->id)
            ->where('key', $factionKey)
            ->first();
        if ($existing) {
            return $existing;
        }

        $row = $this->catalog->faction($factionKey);

        return DemonicFaction::query()->create([
            'world_id' => $world->id,
            'key' => $factionKey,
            'name' => $row['name'] ?? $factionKey,
            'status' => 'dormant',
            'agenda' => $row['name'] ?? $factionKey,
            'themes' => $row['themes'] ?? [],
            'rivals' => $row['rivals'] ?? [],
            'patron_named_key' => $row['patron_named_key'] ?? null,
        ]);
    }

    private function ensureNamed(World $world, string $catalogKey, string $factionKey): NamedDemon
    {
        $existing = NamedDemon::query()
            ->where('world_id', $world->id)
            ->where('catalog_key', $catalogKey)
            ->first();
        if ($existing) {
            $this->backfillIdentity($existing, $catalogKey);

            return $existing;
        }

        $row = $this->catalog->named($catalogKey);
        $faction = DemonicFaction::query()
            ->where('world_id', $world->id)
            ->where('key', $factionKey)
            ->first();

        $demon = NamedDemon::query()->create([
            'world_id' => $world->id,
            'instance_key' => $catalogKey,
            'catalog_key' => $catalogKey,
            'status' => NamedDemonStatus::LATENT,
            'respawn_policy' => $row['respawn_policy'] ?? 'lore_only',
            'faction_id' => $faction?->id,
            'return_authorized' => false,
            'return_min_apocalypse' => (int) ($row['return_min_apocalypse'] ?? 100),
            'true_name' => $row['true_name'] ?? $row['name'],
            'public_alias' => $row['public_alias'] ?? $row['name'],
            'strategy' => $row['strategy'] ?? null,
            'hierarchy' => $row['hierarchy'] ?? null,
            'epithets' => $row['epithets'] ?? [],
            'titles' => $row['titles'] ?? [],
            'themes' => $row['themes'] ?? [],
            'channels' => $row['channels'] ?? [],
            'hidden_true_state' => $row['hidden_true_state'] ?? [],
            'vulnerabilities' => $row['vulnerabilities'] ?? [],
            'known_manifestations' => $row['known_manifestations'] ?? [],
            'physical_manifest_min_apocalypse' => (int) ($row['physical_manifest_min_apocalypse'] ?? 40),
            'physical_manifest_forbidden_phases' => $row['physical_manifest_forbidden_phases'] ?? ['ordinary'],
        ]);

        foreach ($row['objectives'] ?? [] as $objective) {
            DB::table('named_demon_objectives')->insert([
                'world_id' => $world->id,
                'named_demon_id' => $demon->id,
                'key' => $objective['key'],
                'aim' => $objective['aim'] ?? $objective['key'],
                'status' => ObjectiveStatus::OPEN,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach ($row['rivals'] ?? [] as $rival) {
            DB::table('named_demon_rivalries')->insert([
                'world_id' => $world->id,
                'named_demon_id' => $demon->id,
                'rival_catalog_key' => $rival,
                'reason' => 'catalog',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $demon;
    }

    private function backfillIdentity(NamedDemon $demon, string $catalogKey): void
    {
        $row = $this->catalog->named($catalogKey);
        if ($demon->true_name === null || $demon->true_name === '') {
            $demon->fill([
                'true_name' => $row['true_name'] ?? $row['name'],
                'public_alias' => $row['public_alias'] ?? $row['name'],
                'strategy' => $row['strategy'] ?? null,
                'hierarchy' => $row['hierarchy'] ?? null,
                'epithets' => $row['epithets'] ?? [],
                'titles' => $row['titles'] ?? [],
                'themes' => $row['themes'] ?? [],
                'channels' => $row['channels'] ?? [],
                'hidden_true_state' => $row['hidden_true_state'] ?? [],
                'vulnerabilities' => $row['vulnerabilities'] ?? [],
                'known_manifestations' => $row['known_manifestations'] ?? [],
                'physical_manifest_min_apocalypse' => (int) ($row['physical_manifest_min_apocalypse'] ?? 40),
                'physical_manifest_forbidden_phases' => $row['physical_manifest_forbidden_phases'] ?? ['ordinary'],
            ]);
            $demon->save();
        }

        if ($demon->objectives()->count() === 0) {
            foreach ($row['objectives'] ?? [] as $objective) {
                DB::table('named_demon_objectives')->insert([
                    'world_id' => $demon->world_id,
                    'named_demon_id' => $demon->id,
                    'key' => $objective['key'],
                    'aim' => $objective['aim'] ?? $objective['key'],
                    'status' => ObjectiveStatus::OPEN,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        if ($demon->rivalries()->count() === 0) {
            foreach ($row['rivals'] ?? [] as $rival) {
                DB::table('named_demon_rivalries')->insert([
                    'world_id' => $demon->world_id,
                    'named_demon_id' => $demon->id,
                    'rival_catalog_key' => $rival,
                    'reason' => 'catalog',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}
