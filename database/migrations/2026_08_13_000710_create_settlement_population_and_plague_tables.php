<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Package 8: settlement census, plague hosts, displacement, ruin.
 * Territory-level plague_waves / territory_plague_states already exist (000700).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settlements')) {
            Schema::create('settlements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->unsignedBigInteger('territory_id')->nullable();
                $table->unsignedBigInteger('holding_id')->nullable();
                $table->string('settlement_key', 64);
                $table->string('name');
                $table->string('kind', 32);
                $table->string('ruin_state', 32)->default('functioning');
                $table->string('ruin_reason', 64)->nullable();
                $table->unsignedInteger('ticks_abandoned')->default(0);
                $table->boolean('hell_occupation')->default(false);
                $table->timestamps();

                $table->unique(['world_id', 'settlement_key']);
                $table->index(['world_id', 'ruin_state']);
            });

            if (Schema::hasTable('territories')) {
                Schema::table('settlements', function (Blueprint $table) {
                    $table->foreign('territory_id')->references('id')->on('territories')->nullOnDelete();
                });
            }
            if (Schema::hasTable('holdings')) {
                Schema::table('settlements', function (Blueprint $table) {
                    $table->foreign('holding_id')->references('id')->on('holdings')->nullOnDelete();
                });
            }
        }

        if (Schema::hasTable('plague_waves') && Schema::hasTable('settlements') && ! Schema::hasColumn('plague_waves', 'infectiousness')) {
            Schema::table('plague_waves', function (Blueprint $table) {
                $table->unsignedInteger('infectiousness')->default(0);
                $table->unsignedInteger('mortality')->default(0);
                $table->unsignedSmallInteger('incubation_ticks')->default(3);
                $table->unsignedSmallInteger('infectious_ticks')->default(5);
                $table->unsignedInteger('supernatural_amplification')->default(10000);
                $table->boolean('supernatural')->default(false);
                $table->foreignId('origin_settlement_id')->nullable()->constrained('settlements')->nullOnDelete();
                $table->date('peaked_on')->nullable();
            });
        }

        if (! Schema::hasTable('settlement_populations')) {
            Schema::create('settlement_populations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('settlement_id')->constrained('settlements')->cascadeOnDelete();
                $table->unsignedInteger('nobles')->default(0);
                $table->unsignedInteger('clergy')->default(0);
                $table->unsignedInteger('burghers')->default(0);
                $table->unsignedInteger('peasants')->default(0);
                $table->unsignedInteger('unfree')->default(0);
                $table->unsignedInteger('peak_souls')->default(0);
                $table->unsignedTinyInteger('morale')->default(60);
                $table->unsignedTinyInteger('despair')->default(5);
                $table->unsignedTinyInteger('corruption')->default(0);
                $table->unsignedInteger('food_stores')->default(0);
                $table->unsignedInteger('food_yield_per_worker')->default(2);
                $table->unsignedInteger('unburied')->default(0);
                $table->unsignedInteger('graveyard_capacity')->default(0);
                $table->unsignedInteger('incubating')->default(0);
                $table->unsignedInteger('infectious')->default(0);
                $table->unsignedInteger('recovered')->default(0);
                $table->string('quarantine', 32)->default('none');
                $table->string('corpse_handling', 32)->default('consecrated');
                $table->string('clergy_care', 32)->default('parish');
                $table->date('as_of_date')->nullable();
                $table->timestamps();

                $table->unique(['settlement_id']);
            });
        }

        if (! Schema::hasTable('settlement_plague_states')) {
            Schema::create('settlement_plague_states', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('settlement_id')->constrained('settlements')->cascadeOnDelete();
                $table->unsignedBigInteger('wave_id');
                $table->unsignedInteger('incubating')->default(0);
                $table->unsignedInteger('infectious')->default(0);
                $table->unsignedInteger('recovered')->default(0);
                $table->unsignedInteger('dead')->default(0);
                $table->unsignedInteger('local_supernatural_bp')->default(10000);
                $table->json('incubation_batches')->nullable();
                $table->json('infectious_batches')->nullable();
                $table->date('arrived_on')->nullable();
                $table->date('peaked_on')->nullable();
                $table->timestamps();

                $table->unique(['settlement_id', 'wave_id']);
                $table->foreign('wave_id')->references('id')->on('plague_waves')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('plague_hosts')) {
            Schema::create('plague_hosts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->unsignedBigInteger('wave_id');
                $table->string('host_type', 32);
                $table->string('host_key', 64);
                $table->foreignId('location_settlement_id')->nullable()->constrained('settlements')->nullOnDelete();
                $table->unsignedInteger('heads')->default(0);
                $table->unsignedInteger('incubating')->default(0);
                $table->unsignedInteger('infectious')->default(0);
                $table->unsignedInteger('recovered')->default(0);
                $table->unsignedInteger('dead')->default(0);
                $table->unsignedInteger('local_supernatural_bp')->default(10000);
                $table->json('incubation_batches')->nullable();
                $table->json('infectious_batches')->nullable();
                $table->timestamps();

                $table->unique(['world_id', 'host_type', 'host_key', 'wave_id'], 'plague_hosts_unique');
                $table->foreign('wave_id')->references('id')->on('plague_waves')->cascadeOnDelete();
            });
        }

        if (! Schema::hasTable('settlement_links')) {
            Schema::create('settlement_links', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('from_settlement_id')->constrained('settlements')->cascadeOnDelete();
                $table->foreignId('to_settlement_id')->constrained('settlements')->cascadeOnDelete();
                $table->string('vector', 32);
                $table->unsignedInteger('intensity')->default(5000);
                $table->boolean('is_active')->default(true);
                $table->string('army_id', 64)->nullable();
                $table->timestamps();

                $table->index(['world_id', 'vector']);
            });
        }

        if (! Schema::hasTable('displacements')) {
            Schema::create('displacements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->string('flow_key', 64);
                $table->foreignId('from_settlement_id')->constrained('settlements')->cascadeOnDelete();
                $table->foreignId('to_settlement_id')->nullable()->constrained('settlements')->nullOnDelete();
                $table->string('cause', 32);
                $table->unsignedInteger('nobles')->default(0);
                $table->unsignedInteger('clergy')->default(0);
                $table->unsignedInteger('burghers')->default(0);
                $table->unsignedInteger('peasants')->default(0);
                $table->unsignedInteger('unfree')->default(0);
                $table->unsignedInteger('carrying_infectious')->default(0);
                $table->unsignedInteger('carrying_incubating')->default(0);
                $table->string('status', 32)->default('in_transit');
                $table->unsignedInteger('departed_tick')->default(0);
                $table->unsignedInteger('arrived_tick')->nullable();
                $table->date('departed_on')->nullable();
                $table->date('arrived_on')->nullable();
                $table->timestamps();

                $table->unique(['world_id', 'flow_key']);
            });
        }

        if (! Schema::hasTable('ruin_state_histories')) {
            Schema::create('ruin_state_histories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('settlement_id')->constrained('settlements')->cascadeOnDelete();
                $table->string('from_state', 32);
                $table->string('to_state', 32);
                $table->string('reason', 64);
                $table->unsignedInteger('tick')->default(0);
                $table->date('changed_on')->nullable();
                $table->timestamps();

                $table->index(['world_id', 'settlement_id']);
            });
        }

        if (! Schema::hasTable('mass_mortality_events')) {
            Schema::create('mass_mortality_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('settlement_id')->constrained('settlements')->cascadeOnDelete();
                $table->unsignedBigInteger('wave_id')->nullable();
                $table->string('cause', 32);
                $table->unsignedInteger('souls_lost');
                $table->json('deaths_by_class');
                $table->unsignedInteger('workforce_before');
                $table->unsignedInteger('workforce_after');
                $table->unsignedInteger('tax_base_before');
                $table->unsignedInteger('tax_base_after');
                $table->unsignedInteger('levy_base_before');
                $table->unsignedInteger('levy_base_after');
                $table->unsignedInteger('clergy_vacancies')->default(0);
                $table->unsignedTinyInteger('succession_pressure')->default(0);
                $table->boolean('noble_extinction')->default(false);
                $table->boolean('viability_lost')->default(false);
                $table->unsignedTinyInteger('rebellion_pressure')->default(0);
                $table->unsignedTinyInteger('migration_pressure')->default(0);
                $table->unsignedInteger('unburied_corpses')->default(0);
                $table->unsignedTinyInteger('despair')->default(0);
                $table->unsignedTinyInteger('corruption')->default(0);
                $table->string('ruin_state', 32);
                $table->date('occurred_on')->nullable();
                $table->timestamps();

                $table->index(['world_id', 'settlement_id']);
                $table->foreign('wave_id')->references('id')->on('plague_waves')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('mass_mortality_events');
        Schema::dropIfExists('ruin_state_histories');
        Schema::dropIfExists('displacements');
        Schema::dropIfExists('settlement_links');
        Schema::dropIfExists('plague_hosts');
        Schema::dropIfExists('settlement_plague_states');
        Schema::dropIfExists('settlement_populations');
        Schema::dropIfExists('settlements');
    }
};
