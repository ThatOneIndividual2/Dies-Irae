<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persistence for the doctrine kernel. Prefixed so the playable Army/Battle
 * slice can own `armies` / `battles` without a table fight.
 * Does not touch feudalism_game.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('warfare_wars')) {
        Schema::create('warfare_wars', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('world_id');
            $table->unsignedBigInteger('aggressor_id');
            $table->string('aggressor_kind', 32);
            $table->unsignedBigInteger('defender_id');
            $table->string('defender_kind', 32);
            $table->string('kind', 32);
            $table->string('status', 16)->default('active');
            $table->string('goal_type', 32);
            $table->unsignedBigInteger('goal_territory_id')->nullable();
            $table->unsignedInteger('attacker_score')->default(0);
            $table->unsignedInteger('defender_score')->default(0);
            $table->unsignedInteger('attacker_casualties')->default(0);
            $table->unsignedInteger('defender_casualties')->default(0);
            $table->unsignedBigInteger('winner_id')->nullable();
            $table->string('settlement_type', 32)->nullable();
            $table->date('started_on');
            $table->date('ended_on')->nullable();
            $table->timestamps();
            $table->index(['world_id', 'status']);
        });

        Schema::create('warfare_armies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('world_id');
            $table->unsignedBigInteger('war_id');
            $table->unsignedBigInteger('belligerent_id');
            $table->string('nature', 24);
            $table->unsignedBigInteger('territory_id');
            $table->unsignedBigInteger('commander_character_id')->nullable();
            $table->unsignedTinyInteger('morale')->default(80);
            $table->unsignedTinyInteger('supply')->default(80);
            $table->unsignedTinyInteger('fear')->default(0);
            $table->unsignedTinyInteger('corruption_exposure')->default(0);
            $table->unsignedTinyInteger('plague_exposure')->default(0);
            $table->unsignedInteger('manifestation_remaining')->default(0);
            $table->unsignedBigInteger('portal_territory_id')->nullable();
            $table->boolean('starving')->default(false);
            $table->unsignedInteger('days_in_field')->default(0);
            $table->timestamps();
            $table->index(['world_id', 'war_id']);
        });

        Schema::create('warfare_army_stacks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('army_id');
            $table->string('category', 24);
            $table->unsignedInteger('men');
            $table->decimal('quality', 4, 2)->default(1);
            $table->string('label', 64)->nullable();
            $table->boolean('consecrated')->default(false);
            $table->timestamps();
        });

        Schema::create('warfare_sieges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('world_id');
            $table->unsignedBigInteger('war_id');
            $table->unsignedBigInteger('territory_id');
            $table->unsignedBigInteger('attacker_army_id');
            $table->unsignedInteger('fortification');
            $table->unsignedInteger('garrison');
            $table->unsignedTinyInteger('progress')->default(0);
            $table->string('method', 24)->default('siege');
            $table->boolean('fallen')->default(false);
            $table->timestamps();
        });

        Schema::create('warfare_occupations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('world_id');
            $table->unsignedBigInteger('war_id');
            $table->unsignedBigInteger('territory_id');
            $table->string('mode', 24);
            $table->unsignedBigInteger('controller_belligerent_id');
            $table->unsignedBigInteger('owner_belligerent_id');
            $table->boolean('hell_overlay')->default(false);
            $table->boolean('infiltration')->default(false);
            $table->date('started_on');
            $table->date('ended_on')->nullable();
            $table->timestamps();
            $table->unique(['war_id', 'territory_id']);
        });

        Schema::create('warfare_battlefield_aftermaths', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('world_id');
            $table->unsignedBigInteger('war_id');
            $table->unsignedBigInteger('territory_id');
            $table->string('war_kind', 32);
            $table->unsignedInteger('corpses')->default(0);
            $table->string('corpse_handling', 24);
            $table->unsignedInteger('mundane_disease_risk')->default(0);
            $table->unsignedInteger('plague_exposure')->default(0);
            $table->integer('settlement_morale_delta')->default(0);
            $table->integer('corruption_delta')->default(0);
            $table->integer('local_faith_delta')->default(0);
            $table->unsignedInteger('refugees')->default(0);
            $table->string('refugee_cause', 24)->nullable();
            $table->string('sanctity_after', 24);
            $table->boolean('possession_event')->default(false);
            $table->boolean('desecration')->default(false);
            $table->timestamps();
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('warfare_battlefield_aftermaths');
        Schema::dropIfExists('warfare_occupations');
        Schema::dropIfExists('warfare_sieges');
        Schema::dropIfExists('warfare_army_stacks');
        Schema::dropIfExists('warfare_armies');
        Schema::dropIfExists('warfare_wars');
    }
};
