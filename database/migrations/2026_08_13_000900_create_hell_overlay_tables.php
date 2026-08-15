<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hell overlay. Additive. Does not replace demonic_factions / demonic_threats stubs.
 * Demonic factions remain infernal polities, not realms.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('demonic_factions') && !Schema::hasColumn('demonic_factions', 'themes')) {
            Schema::table('demonic_factions', function (Blueprint $table) {
                $table->json('themes')->nullable();
                $table->json('rivals')->nullable();
                $table->string('patron_named_key', 64)->nullable();
            });
        }

        if (!Schema::hasTable('named_demons')) {
        Schema::create('named_demons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->string('instance_key', 64);
            $table->string('catalog_key', 64);
            $table->string('status', 32);
            $table->string('respawn_policy', 32);
            $table->foreignId('territory_id')->nullable()->constrained('territories')->nullOnDelete();
            $table->foreignId('host_character_id')->nullable()->constrained('characters')->nullOnDelete();
            $table->foreignId('faction_id')->nullable()->constrained('demonic_factions')->nullOnDelete();
            $table->date('destroyed_on')->nullable();
            $table->boolean('return_authorized')->default(false);
            $table->string('return_lore_reason')->nullable();
            $table->unsignedTinyInteger('return_min_apocalypse')->default(100);
            $table->timestamps();

            $table->unique(['world_id', 'catalog_key']);
            $table->unique(['world_id', 'instance_key']);
            $table->index(['world_id', 'status']);
        });

        Schema::create('named_demon_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('named_demon_id')->constrained('named_demons')->cascadeOnDelete();
            $table->string('status', 32);
            $table->string('reason', 128)->nullable();
            $table->date('changed_on');
            $table->timestamps();

            $table->index(['world_id', 'named_demon_id']);
        });

        Schema::create('demonic_influences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignId('territory_id')->nullable()->constrained('territories')->nullOnDelete();
            $table->string('stage', 32);
            $table->string('source_catalog_key', 64)->nullable();
            $table->foreignId('named_demon_id')->nullable()->constrained('named_demons')->nullOnDelete();
            $table->foreignId('faction_id')->nullable()->constrained('demonic_factions')->nullOnDelete();
            $table->unsignedTinyInteger('intensity')->default(0);
            $table->boolean('is_active')->default(true);
            $table->date('started_on');
            $table->date('ended_on')->nullable();
            $table->timestamps();

            $table->index(['world_id', 'character_id', 'is_active'], 'demonic_influence_current_idx');
        });

        Schema::create('territory_incursions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('territory_id')->constrained('territories')->cascadeOnDelete();
            $table->string('state', 32);
            $table->unsignedTinyInteger('corruption')->default(0);
            $table->unsignedTinyInteger('local_manifestation')->default(0);
            $table->unsignedTinyInteger('cult_activity')->default(0);
            $table->boolean('settlement_corrupted')->default(false);
            $table->foreignId('stronghold_faction_id')->nullable()->constrained('demonic_factions')->nullOnDelete();
            $table->boolean('is_current')->nullable();
            $table->date('changed_on');
            $table->timestamps();

            $table->unique(['territory_id', 'is_current']);
            $table->index(['world_id', 'state']);
        });

        Schema::create('supernatural_overlays', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('territory_id')->constrained('territories')->cascadeOnDelete();
            $table->string('kind', 32);
            $table->unsignedTinyInteger('intensity')->default(0);
            $table->boolean('is_current')->nullable();
            $table->date('changed_on');
            $table->timestamps();

            $table->unique(['territory_id', 'is_current']);
            $table->index(['world_id', 'territory_id']);
        });

        Schema::create('cults', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('territory_id')->constrained('territories')->cascadeOnDelete();
            $table->foreignId('faction_id')->constrained('demonic_factions')->cascadeOnDelete();
            $table->unsignedTinyInteger('activity')->default(0);
            $table->unsignedTinyInteger('strength')->default(0);
            $table->boolean('revealed')->default(false);
            $table->boolean('destroyed')->default(false);
            $table->string('patron_named_key', 64)->nullable();
            $table->timestamps();

            $table->index(['world_id', 'territory_id']);
        });

        Schema::create('infernal_breaches', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('territory_id')->constrained('territories')->cascadeOnDelete();
            $table->boolean('open')->default(true);
            $table->foreignId('named_demon_id')->nullable()->constrained('named_demons')->nullOnDelete();
            $table->date('opened_on');
            $table->date('closed_on')->nullable();
            $table->timestamps();

            $table->index(['world_id', 'territory_id', 'open']);
        });

        Schema::create('demonic_hosts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('territory_id')->constrained('territories')->cascadeOnDelete();
            $table->string('kind', 32);
            $table->foreignId('faction_id')->nullable()->constrained('demonic_factions')->nullOnDelete();
            $table->string('commander_catalog_key', 64)->nullable();
            $table->foreignId('named_demon_id')->nullable()->constrained('named_demons')->nullOnDelete();
            $table->unsignedInteger('strength')->default(0);
            $table->foreignId('bound_breach_id')->nullable()->constrained('infernal_breaches')->nullOnDelete();
            $table->boolean('collapsed')->default(false);
            $table->json('composition')->nullable();
            $table->timestamps();

            $table->index(['world_id', 'territory_id']);
        });

        Schema::create('corrupted_armies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('territory_id')->constrained('territories')->cascadeOnDelete();
            $table->unsignedTinyInteger('corruption')->default(0);
            $table->unsignedInteger('strength')->default(0);
            $table->foreignId('original_realm_id')->nullable()->constrained('realms')->nullOnDelete();
            $table->foreignId('original_commander_character_id')->nullable()->constrained('characters')->nullOnDelete();
            $table->boolean('cleansed')->default(false);
            $table->timestamps();

            $table->index(['world_id', 'territory_id']);
        });

        Schema::create('countermeasure_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('territory_id')->constrained('territories')->cascadeOnDelete();
            $table->string('kind', 32);
            $table->string('result', 32);
            $table->decimal('score', 8, 4);
            $table->foreignId('actor_character_id')->nullable()->constrained('characters')->nullOnDelete();
            $table->boolean('character_died')->default(false);
            $table->boolean('breach_closed')->default(false);
            $table->json('factors')->nullable();
            $table->date('attempted_on');
            $table->timestamps();

            $table->index(['world_id', 'territory_id']);
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('countermeasure_attempts');
        Schema::dropIfExists('corrupted_armies');
        Schema::dropIfExists('demonic_hosts');
        Schema::dropIfExists('infernal_breaches');
        Schema::dropIfExists('cults');
        Schema::dropIfExists('supernatural_overlays');
        Schema::dropIfExists('territory_incursions');
        Schema::dropIfExists('demonic_influences');
        Schema::dropIfExists('named_demon_status_histories');
        Schema::dropIfExists('named_demons');

        Schema::table('demonic_factions', function (Blueprint $table) {
            $table->dropColumn(['themes', 'rivals', 'patron_named_key']);
        });
    }
};
