<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('settlement_economies')) {
            Schema::create('settlement_economies', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('world_id');
                $table->unsignedBigInteger('settlement_id');
                $table->unsignedTinyInteger('land_quality')->default(70);
                $table->unsignedInteger('arable')->default(0);
                $table->unsignedInteger('planted')->default(0);
                $table->unsignedInteger('crop_standing')->default(0);
                $table->unsignedInteger('livestock')->default(0);
                $table->unsignedInteger('workshops')->default(0);
                $table->unsignedTinyInteger('market_activity')->default(40);
                $table->integer('gold')->default(0);
                $table->unsignedInteger('monastery_stores')->default(0);
                $table->unsignedTinyInteger('church_legitimacy')->default(50);
                $table->boolean('monastery_overwhelmed')->default(false);
                $table->unsignedInteger('transport_capacity')->default(400);
                $table->unsignedInteger('weather_bp')->default(10000);
                $table->unsignedInteger('war_disruption_bp')->default(0);
                $table->unsignedInteger('blight_bp')->default(0);
                $table->unsignedInteger('labor_penalty_bp')->default(0);
                $table->unsignedInteger('abandoned_land')->default(0);
                $table->unsignedSmallInteger('regional_price')->default(100);
                $table->unsignedInteger('last_harvest')->default(0);
                $table->timestamps();

                $table->unique(['settlement_id']);
                $table->index(['world_id']);
            });
        }

        if (! Schema::hasTable('famine_states')) {
            Schema::create('famine_states', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('world_id');
                $table->unsignedBigInteger('settlement_id');
                $table->string('stage', 32)->default('none');
                $table->unsignedInteger('deficit')->default(0);
                $table->unsignedInteger('deficit_bp')->default(0);
                $table->unsignedInteger('days_of_food')->default(0);
                $table->unsignedTinyInteger('malnutrition')->default(0);
                $table->unsignedInteger('consecutive_hungry_days')->default(0);
                $table->unsignedInteger('starvation_deaths')->default(0);
                $table->unsignedTinyInteger('unrest')->default(0);
                $table->unsignedTinyInteger('crime')->default(0);
                $table->unsignedTinyInteger('disease_susceptibility')->default(0);
                $table->unsignedTinyInteger('military_desertion')->default(0);
                $table->unsignedInteger('tax_multiplier_bp')->default(10000);
                $table->unsignedTinyInteger('desperation')->default(0);
                $table->string('extreme_hook', 64)->nullable();
                $table->boolean('is_current')->nullable();
                $table->date('as_of_date')->nullable();
                $table->timestamps();

                $table->unique(['settlement_id', 'is_current'], 'famine_state_current_uq');
                $table->index(['world_id', 'stage']);
            });
        }

        if (! Schema::hasTable('food_movements')) {
            Schema::create('food_movements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('world_id');
                $table->unsignedBigInteger('from_settlement_id');
                $table->unsignedBigInteger('to_settlement_id');
                $table->unsignedInteger('amount');
                $table->string('mode', 32);
                $table->string('actor', 64);
                $table->unsignedInteger('gold_paid')->default(0);
                $table->string('status', 32)->default('moved');
                $table->date('moved_on')->nullable();
                $table->timestamps();

                $table->index(['world_id', 'mode']);
            });
        }

        if (! Schema::hasTable('economy_trade_routes')) {
            Schema::create('economy_trade_routes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('world_id');
                $table->unsignedBigInteger('from_settlement_id');
                $table->unsignedBigInteger('to_settlement_id');
                $table->unsignedInteger('capacity')->default(800);
                $table->boolean('is_blocked')->default(false);
                $table->boolean('is_haunted')->default(false);
                $table->timestamps();

                $table->unique(['from_settlement_id', 'to_settlement_id'], 'economy_route_pair_uq');
            });
        }

        if (! Schema::hasTable('harvest_records')) {
            Schema::create('harvest_records', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('world_id');
                $table->unsignedBigInteger('settlement_id');
                $table->unsignedSmallInteger('year');
                $table->string('phase', 16);
                $table->unsignedInteger('grain')->default(0);
                $table->json('modifiers')->nullable();
                $table->date('recorded_on')->nullable();
                $table->timestamps();

                $table->index(['world_id', 'year', 'phase']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('harvest_records');
        Schema::dropIfExists('economy_trade_routes');
        Schema::dropIfExists('food_movements');
        Schema::dropIfExists('famine_states');
        Schema::dropIfExists('settlement_economies');
    }
};
