<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Holy Orders as hybrid religious-military corporations.
 * Additive. Does not replace Church spiritual offices or ordinary armies.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('holy_orders')) {
            Schema::table('holy_orders', function (Blueprint $table) {
                if (!Schema::hasColumn('holy_orders', 'allegiance_type')) {
                    $table->string('allegiance_type', 32)->default('independent');
                }
                if (!Schema::hasColumn('holy_orders', 'status')) {
                    $table->string('status', 32)->default('founding');
                }
                if (!Schema::hasColumn('holy_orders', 'legitimacy')) {
                    $table->string('legitimacy', 32)->default('unrecognized');
                }
                if (!Schema::hasColumn('holy_orders', 'papal_recognition_status')) {
                    $table->string('papal_recognition_status', 32)->default('none');
                }
                if (!Schema::hasColumn('holy_orders', 'headquarters_holding_id')) {
                    $table->unsignedBigInteger('headquarters_holding_id')->nullable();
                }
                if (!Schema::hasColumn('holy_orders', 'treasury')) {
                    $table->integer('treasury')->default(0);
                }
                if (!Schema::hasColumn('holy_orders', 'manpower_cap')) {
                    $table->unsignedInteger('manpower_cap')->default(80);
                }
                if (!Schema::hasColumn('holy_orders', 'manpower_current')) {
                    $table->unsignedInteger('manpower_current')->default(0);
                }
                if (!Schema::hasColumn('holy_orders', 'corruption')) {
                    $table->unsignedTinyInteger('corruption')->default(0);
                }
                if (!Schema::hasColumn('holy_orders', 'reputation')) {
                    $table->unsignedTinyInteger('reputation')->default(40);
                }
                if (!Schema::hasColumn('holy_orders', 'morale')) {
                    $table->unsignedTinyInteger('morale')->default(70);
                }
                if (!Schema::hasColumn('holy_orders', 'consecration')) {
                    $table->unsignedTinyInteger('consecration')->default(40);
                }
                if (!Schema::hasColumn('holy_orders', 'corruption_resistance')) {
                    $table->unsignedTinyInteger('corruption_resistance')->default(50);
                }
                if (!Schema::hasColumn('holy_orders', 'controversial')) {
                    $table->boolean('controversial')->default(false);
                }
                if (!Schema::hasColumn('holy_orders', 'schism_status')) {
                    $table->string('schism_status', 32)->default('none');
                }
                if (!Schema::hasColumn('holy_orders', 'founded_date')) {
                    $table->date('founded_date')->nullable();
                }
                if (!Schema::hasColumn('holy_orders', 'dissolved_date')) {
                    $table->date('dissolved_date')->nullable();
                }
                if (!Schema::hasColumn('holy_orders', 'excommunicated_date')) {
                    $table->date('excommunicated_date')->nullable();
                }
            });

            try {
                Schema::table('holy_orders', function (Blueprint $table) {
                    $table->foreign('headquarters_holding_id')->references('id')->on('holdings')->nullOnDelete();
                });
            } catch (\Throwable $e) {
            }
        }

        if (Schema::hasTable('relic_custodies') && !Schema::hasColumn('relic_custodies', 'holy_order_id')) {
            Schema::table('relic_custodies', function (Blueprint $table) {
                $table->unsignedBigInteger('holy_order_id')->nullable();
                $table->unsignedBigInteger('holy_order_house_id')->nullable();
            });
        }

        if (!Schema::hasTable('holy_order_houses')) {
            Schema::create('holy_order_houses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('holy_order_id')->constrained('holy_orders')->cascadeOnDelete();
                $table->foreignId('holding_id')->constrained('holdings')->cascadeOnDelete();
                $table->foreignId('territory_id')->constrained('territories')->cascadeOnDelete();
                $table->unsignedBigInteger('commander_character_id')->nullable();
                $table->string('name');
                $table->string('house_type', 32)->default('commandery');
                $table->boolean('is_headquarters')->default(false);
                $table->boolean('is_active')->default(true);
                $table->date('founded_date');
                $table->date('suppressed_date')->nullable();
                $table->timestamps();
                $table->foreign('commander_character_id')->references('id')->on('characters')->nullOnDelete();
                $table->index(['holy_order_id', 'is_active']);
            });
        }

        if (!Schema::hasTable('holy_order_memberships')) {
            Schema::create('holy_order_memberships', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('holy_order_id')->constrained('holy_orders')->cascadeOnDelete();
                $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
                $table->unsignedBigInteger('house_id')->nullable();
                $table->string('member_rank', 32);
                $table->date('recruited_date');
                $table->date('ended_date')->nullable();
                $table->string('end_reason', 64)->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();
                $table->foreign('house_id')->references('id')->on('holy_order_houses')->nullOnDelete();
                $table->unique(['character_id', 'is_current'], 'holy_order_member_current_uq');
                $table->index(['holy_order_id', 'member_rank', 'is_current'], 'holy_order_member_rank_idx');
            });
        }

        if (!Schema::hasTable('holy_order_vows')) {
            Schema::create('holy_order_vows', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('membership_id')->constrained('holy_order_memberships')->cascadeOnDelete();
                $table->string('vow_type', 32);
                $table->string('integrity', 32)->default('kept');
                $table->date('started_date');
                $table->date('ended_date')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();
                $table->index(['membership_id', 'is_current']);
            });
        }

        if (!Schema::hasTable('holy_order_holdings')) {
            Schema::create('holy_order_holdings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('holy_order_id')->constrained('holy_orders')->cascadeOnDelete();
                $table->foreignId('holding_id')->constrained('holdings')->cascadeOnDelete();
                $table->unsignedBigInteger('house_id')->nullable();
                $table->unsignedBigInteger('donor_character_id')->nullable();
                $table->unsignedBigInteger('donor_title_id')->nullable();
                $table->string('acquisition_type', 32)->default('donation');
                $table->date('acquired_date');
                $table->date('confiscated_date')->nullable();
                $table->unsignedBigInteger('confiscated_by_character_id')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();
                $table->foreign('house_id')->references('id')->on('holy_order_houses')->nullOnDelete();
                $table->foreign('donor_character_id')->references('id')->on('characters')->nullOnDelete();
                $table->foreign('donor_title_id')->references('id')->on('titles')->nullOnDelete();
                $table->unique(['holding_id', 'is_current'], 'holy_order_holding_current_uq');
            });
        }

        if (!Schema::hasTable('holy_order_treasury_entries')) {
            Schema::create('holy_order_treasury_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('holy_order_id')->constrained('holy_orders')->cascadeOnDelete();
                $table->integer('amount');
                $table->string('reason', 64);
                $table->unsignedBigInteger('source_character_id')->nullable();
                $table->unsignedBigInteger('source_realm_id')->nullable();
                $table->date('occurred_date');
                $table->timestamps();
                $table->foreign('source_character_id')->references('id')->on('characters')->nullOnDelete();
                $table->index(['holy_order_id', 'occurred_date']);
            });
        }

        if (!Schema::hasTable('holy_order_patronages')) {
            Schema::create('holy_order_patronages', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('holy_order_id')->constrained('holy_orders')->cascadeOnDelete();
                $table->foreignId('patron_character_id')->constrained('characters')->cascadeOnDelete();
                $table->unsignedBigInteger('patron_realm_id')->nullable();
                $table->unsignedBigInteger('patron_title_id')->nullable();
                $table->string('status', 32)->default('active');
                $table->date('started_date');
                $table->date('ended_date')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();
                $table->foreign('patron_realm_id')->references('id')->on('realms')->nullOnDelete();
                $table->foreign('patron_title_id')->references('id')->on('titles')->nullOnDelete();
                $table->unique(['holy_order_id', 'is_current'], 'holy_order_patron_current_uq');
            });
        }

        if (!Schema::hasTable('holy_order_recognitions')) {
            Schema::create('holy_order_recognitions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('holy_order_id')->constrained('holy_orders')->cascadeOnDelete();
                $table->unsignedBigInteger('granted_by_character_id')->nullable();
                $table->string('status', 32);
                $table->string('bull_key', 128)->nullable();
                $table->date('started_date');
                $table->date('ended_date')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();
                $table->foreign('granted_by_character_id')->references('id')->on('characters')->nullOnDelete();
                $table->unique(['holy_order_id', 'is_current'], 'holy_order_recog_current_uq');
            });
        }

        if (!Schema::hasTable('holy_order_missions')) {
            Schema::create('holy_order_missions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('holy_order_id')->constrained('holy_orders')->cascadeOnDelete();
                $table->string('mission_type', 32);
                $table->string('status', 32)->default('active');
                $table->unsignedBigInteger('assigned_by_character_id')->nullable();
                $table->date('started_date');
                $table->date('ended_date')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();
                $table->foreign('assigned_by_character_id')->references('id')->on('characters')->nullOnDelete();
                $table->index(['holy_order_id', 'mission_type']);
            });
        }

        if (!Schema::hasTable('holy_order_forces')) {
            Schema::create('holy_order_forces', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('holy_order_id')->constrained('holy_orders')->cascadeOnDelete();
                $table->unsignedBigInteger('house_id')->nullable();
                $table->unsignedBigInteger('commander_character_id')->nullable();
                $table->unsignedBigInteger('stationed_territory_id')->nullable();
                $table->unsignedBigInteger('warfare_war_id')->nullable();
                $table->unsignedBigInteger('warfare_army_id')->nullable();
                $table->unsignedInteger('knights')->default(0);
                $table->unsignedInteger('sergeants')->default(0);
                $table->unsignedInteger('chaplains')->default(0);
                $table->decimal('quality_modifier', 6, 3)->default(1);
                $table->json('modifier_breakdown')->nullable();
                $table->string('status', 32)->default('garrison');
                $table->string('target_kind', 32)->nullable();
                $table->date('raised_date');
                $table->date('disbanded_date')->nullable();
                $table->timestamps();
                $table->foreign('house_id')->references('id')->on('holy_order_houses')->nullOnDelete();
                $table->foreign('commander_character_id')->references('id')->on('characters')->nullOnDelete();
                $table->foreign('stationed_territory_id')->references('id')->on('territories')->nullOnDelete();
                $table->index(['holy_order_id', 'status']);
            });
        }

        if (!Schema::hasTable('holy_order_schisms')) {
            Schema::create('holy_order_schisms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('holy_order_id')->constrained('holy_orders')->cascadeOnDelete();
                $table->unsignedBigInteger('rival_character_id')->nullable();
                $table->string('cause', 64)->nullable();
                $table->date('started_date');
                $table->date('ended_date')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();
                $table->foreign('rival_character_id')->references('id')->on('characters')->nullOnDelete();
                $table->unique(['holy_order_id', 'is_current'], 'holy_order_schism_current_uq');
            });
        }

        try {
            Schema::table('relic_custodies', function (Blueprint $table) {
                $table->foreign('holy_order_id')->references('id')->on('holy_orders')->nullOnDelete();
                $table->foreign('holy_order_house_id')->references('id')->on('holy_order_houses')->nullOnDelete();
            });
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('holy_order_schisms');
        Schema::dropIfExists('holy_order_forces');
        Schema::dropIfExists('holy_order_missions');
        Schema::dropIfExists('holy_order_recognitions');
        Schema::dropIfExists('holy_order_patronages');
        Schema::dropIfExists('holy_order_treasury_entries');
        Schema::dropIfExists('holy_order_holdings');
        Schema::dropIfExists('holy_order_vows');
        Schema::dropIfExists('holy_order_memberships');
        Schema::dropIfExists('holy_order_houses');
    }
};
