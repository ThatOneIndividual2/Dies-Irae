<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Opening campaign tables for Europa 1347. Additive. Does not alter the Provence slice rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('campaign_states')) {
            Schema::create('campaign_states', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->string('scenario_key', 64);
                $table->string('player_archetype', 32)->nullable();
                $table->unsignedBigInteger('player_character_id')->nullable();
                $table->date('opening_until');
                $table->unsignedInteger('pulse_count')->default(0);
                $table->date('last_pulsed_on')->nullable();
                $table->json('flags')->nullable();
                $table->json('fired_families')->nullable();
                $table->json('cooldowns')->nullable();
                $table->json('revealed_mechanics')->nullable();
                $table->timestamps();
                $table->unique('world_id');
                $table->foreign('player_character_id')->references('id')->on('characters')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('campaign_evidences')) {
            Schema::create('campaign_evidences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->unsignedBigInteger('observer_character_id')->nullable();
                $table->string('key', 128);
                $table->string('family', 64);
                $table->string('interpretation');
                $table->string('certainty', 32)->default('rumor');
                $table->unsignedBigInteger('territory_id')->nullable();
                $table->date('recorded_on');
                $table->json('payload')->nullable();
                $table->timestamps();
                $table->index(['world_id', 'family']);
                $table->foreign('observer_character_id')->references('id')->on('characters')->nullOnDelete();
                $table->foreign('territory_id')->references('id')->on('territories')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('campaign_goal_progress')) {
            Schema::create('campaign_goal_progress', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->unsignedBigInteger('character_id')->nullable();
                $table->string('goal_key', 64);
                $table->string('status', 32)->default('available');
                $table->unsignedTinyInteger('progress')->default(0);
                $table->json('notes')->nullable();
                $table->timestamps();
                $table->unique(['world_id', 'character_id', 'goal_key'], 'campaign_goal_char_uq');
                $table->foreign('character_id')->references('id')->on('characters')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('trade_routes')) {
            Schema::create('trade_routes', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->string('key', 128);
                $table->foreignId('from_territory_id')->constrained('territories')->cascadeOnDelete();
                $table->foreignId('to_territory_id')->constrained('territories')->cascadeOnDelete();
                $table->string('goods', 64)->nullable();
                $table->unsignedTinyInteger('volume')->default(50);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->unique(['world_id', 'key']);
            });
        }

        if (!Schema::hasTable('harvest_states')) {
            Schema::create('harvest_states', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('territory_id')->constrained('territories')->cascadeOnDelete();
                $table->unsignedSmallInteger('harvest_year');
                $table->string('status', 32)->default('sound');
                $table->unsignedTinyInteger('yield_index')->default(70);
                $table->date('recorded_on');
                $table->timestamps();
                $table->unique(['territory_id', 'harvest_year']);
            });
        }

        if (!Schema::hasTable('local_wars')) {
            Schema::create('local_wars', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->string('key', 128);
                $table->string('name');
                $table->string('status', 32)->default('active');
                $table->unsignedBigInteger('attacker_realm_id')->nullable();
                $table->unsignedBigInteger('defender_realm_id')->nullable();
                $table->unsignedBigInteger('theater_territory_id')->nullable();
                $table->date('started_date');
                $table->timestamps();
                $table->unique(['world_id', 'key']);
                $table->foreign('attacker_realm_id')->references('id')->on('realms')->nullOnDelete();
                $table->foreign('defender_realm_id')->references('id')->on('realms')->nullOnDelete();
                $table->foreign('theater_territory_id')->references('id')->on('territories')->nullOnDelete();
            });
        }

        if (Schema::hasTable('game_events') && !Schema::hasColumn('game_events', 'catalog_family')) {
            Schema::table('game_events', function (Blueprint $table) {
                $table->string('catalog_family', 64)->nullable()->after('event_key');
                $table->unsignedBigInteger('audience_character_id')->nullable()->after('payload');
                $table->unsignedBigInteger('territory_id')->nullable()->after('audience_character_id');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('game_events') && Schema::hasColumn('game_events', 'catalog_family')) {
            Schema::table('game_events', function (Blueprint $table) {
                $table->dropColumn(['catalog_family', 'audience_character_id', 'territory_id']);
            });
        }
        Schema::dropIfExists('local_wars');
        Schema::dropIfExists('harvest_states');
        Schema::dropIfExists('trade_routes');
        Schema::dropIfExists('campaign_goal_progress');
        Schema::dropIfExists('campaign_evidences');
        Schema::dropIfExists('campaign_states');
    }
};
