<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Playable vertical-slice columns and tables. Additive on the reconstruction kernel.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users') && !Schema::hasColumn('users', 'world_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignId('world_id')->nullable()->after('id')->constrained('worlds')->nullOnDelete();
            });
        }

        if (Schema::hasTable('characters')) {
            Schema::table('characters', function (Blueprint $table) {
                if (!Schema::hasColumn('characters', 'residence_territory_id')) {
                    $table->unsignedBigInteger('residence_territory_id')->nullable();
                }
                if (!Schema::hasColumn('characters', 'treasury')) {
                    $table->integer('treasury')->default(0);
                }
            });
        }

        if (Schema::hasTable('territories')) {
            Schema::table('territories', function (Blueprint $table) {
                if (!Schema::hasColumn('territories', 'levy_available')) {
                    $table->unsignedInteger('levy_available')->default(0);
                }
                if (!Schema::hasColumn('territories', 'food_stores')) {
                    $table->unsignedInteger('food_stores')->default(0);
                }
                if (!Schema::hasColumn('territories', 'map_box')) {
                    $table->json('map_box')->nullable();
                }
            });
        }

        if (Schema::hasTable('faiths') && !Schema::hasColumn('faiths', 'is_primary')) {
            Schema::table('faiths', function (Blueprint $table) {
                $table->boolean('is_primary')->default(false);
            });
        }

        if (Schema::hasTable('monasteries')) {
            Schema::table('monasteries', function (Blueprint $table) {
                if (!Schema::hasColumn('monasteries', 'stores')) {
                    $table->integer('stores')->default(0);
                }
                if (!Schema::hasColumn('monasteries', 'territory_id')) {
                    $table->unsignedBigInteger('territory_id')->nullable();
                }
                if (!Schema::hasColumn('monasteries', 'see_id')) {
                    $table->unsignedBigInteger('see_id')->nullable();
                }
            });
        }

        if (Schema::hasTable('cults')) {
            Schema::table('cults', function (Blueprint $table) {
                if (!Schema::hasColumn('cults', 'name')) {
                    $table->string('name')->nullable();
                }
                if (!Schema::hasColumn('cults', 'key')) {
                    $table->string('key', 128)->nullable();
                }
            });
        }

        if (Schema::hasTable('plague_waves') && !Schema::hasColumn('plague_waves', 'strain')) {
            Schema::table('plague_waves', function (Blueprint $table) {
                $table->string('strain', 32)->nullable();
            });
        }

        if (Schema::hasTable('territory_plague_states')) {
            Schema::table('territory_plague_states', function (Blueprint $table) {
                if (!Schema::hasColumn('territory_plague_states', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
            });
        }

        if (Schema::hasTable('sees')) {
            Schema::table('sees', function (Blueprint $table) {
                if (!Schema::hasColumn('sees', 'parent_see_id')) {
                    $table->unsignedBigInteger('parent_see_id')->nullable();
                }
                if (!Schema::hasColumn('sees', 'ordinary_office_id')) {
                    $table->unsignedBigInteger('ordinary_office_id')->nullable();
                }
                if (!Schema::hasColumn('sees', 'seat_holding_id')) {
                    $table->unsignedBigInteger('seat_holding_id')->nullable();
                }
                if (!Schema::hasColumn('sees', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
                if (!Schema::hasColumn('sees', 'is_exempt')) {
                    $table->boolean('is_exempt')->default(false);
                }
            });

            if (Schema::hasColumn('sees', 'church_province_id')) {
                \Illuminate\Support\Facades\DB::statement('ALTER TABLE sees MODIFY church_province_id BIGINT UNSIGNED NULL');
            }
        }

        if (Schema::hasTable('spiritual_offices')) {
            Schema::table('spiritual_offices', function (Blueprint $table) {
                if (!Schema::hasColumn('spiritual_offices', 'appointment_mode')) {
                    $table->string('appointment_mode', 32)->nullable();
                }
                if (!Schema::hasColumn('spiritual_offices', 'monastery_id')) {
                    $table->unsignedBigInteger('monastery_id')->nullable();
                }
                if (!Schema::hasColumn('spiritual_offices', 'religious_order_id')) {
                    $table->unsignedBigInteger('religious_order_id')->nullable();
                }
                if (!Schema::hasColumn('spiritual_offices', 'holy_order_id')) {
                    $table->unsignedBigInteger('holy_order_id')->nullable();
                }
                if (!Schema::hasColumn('spiritual_offices', 'is_papal_apex')) {
                    $table->boolean('is_papal_apex')->default(false);
                }
            });
        }

        if (Schema::hasTable('church_provinces') && !Schema::hasColumn('church_provinces', 'metropolitan_see_id')) {
            Schema::table('church_provinces', function (Blueprint $table) {
                $table->unsignedBigInteger('metropolitan_see_id')->nullable();
            });
        }

        if (Schema::hasTable('monasteries')) {
            Schema::table('monasteries', function (Blueprint $table) {
                if (!Schema::hasColumn('monasteries', 'religious_order_id')) {
                    $table->unsignedBigInteger('religious_order_id')->nullable();
                }
                if (!Schema::hasColumn('monasteries', 'abbot_office_id')) {
                    $table->unsignedBigInteger('abbot_office_id')->nullable();
                }
                if (!Schema::hasColumn('monasteries', 'is_exempt')) {
                    $table->boolean('is_exempt')->default(false);
                }
                if (!Schema::hasColumn('monasteries', 'is_active')) {
                    $table->boolean('is_active')->default(true);
                }
            });
        }

        if (Schema::hasTable('spiritual_office_holderships')) {
            Schema::table('spiritual_office_holderships', function (Blueprint $table) {
                if (!Schema::hasColumn('spiritual_office_holderships', 'acquisition_type')) {
                    $table->string('acquisition_type', 32)->default('appointment');
                }
                if (!Schema::hasColumn('spiritual_office_holderships', 'legitimacy')) {
                    $table->string('legitimacy', 32)->default('recognized');
                }
                if (!Schema::hasColumn('spiritual_office_holderships', 'appointed_by_character_id')) {
                    $table->unsignedBigInteger('appointed_by_character_id')->nullable();
                }
                if (!Schema::hasColumn('spiritual_office_holderships', 'previous_holdership_id')) {
                    $table->unsignedBigInteger('previous_holdership_id')->nullable();
                }
                if (!Schema::hasColumn('spiritual_office_holderships', 'appointment_id')) {
                    $table->unsignedBigInteger('appointment_id')->nullable();
                }
            });
        }

        if (Schema::hasTable('clergy_statuses')) {
            Schema::table('clergy_statuses', function (Blueprint $table) {
                if (!Schema::hasColumn('clergy_statuses', 'orders_grade')) {
                    $table->string('orders_grade', 32)->nullable();
                }
                if (!Schema::hasColumn('clergy_statuses', 'religious_state')) {
                    $table->string('religious_state', 32)->default('none');
                }
                if (!Schema::hasColumn('clergy_statuses', 'regularity')) {
                    $table->string('regularity', 32)->default('regular');
                }
                if (!Schema::hasColumn('clergy_statuses', 'religious_order_id')) {
                    $table->unsignedBigInteger('religious_order_id')->nullable();
                }
                if (!Schema::hasColumn('clergy_statuses', 'monastery_id')) {
                    $table->unsignedBigInteger('monastery_id')->nullable();
                }
            });
        }

        if (Schema::hasTable('corruption_states') && !Schema::hasColumn('corruption_states', 'recorded_on')) {
            Schema::table('corruption_states', function (Blueprint $table) {
                $table->date('recorded_on')->nullable();
            });
        }

        if (Schema::hasTable('apocalypse_states') && !Schema::hasColumn('apocalypse_states', 'stage')) {
            Schema::table('apocalypse_states', function (Blueprint $table) {
                $table->string('stage', 64)->nullable();
                $table->json('signs')->nullable();
                $table->date('stage_entered_date')->nullable();
            });
        }

        if (!Schema::hasTable('territory_adjacencies')) {
            Schema::create('territory_adjacencies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->unsignedBigInteger('from_territory_id');
                $table->unsignedBigInteger('to_territory_id');
                $table->timestamps();
                $table->unique(['from_territory_id', 'to_territory_id']);
            });
        }

        if (!Schema::hasTable('church_relations')) {
            Schema::create('church_relations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
                $table->unsignedBigInteger('see_id')->nullable();
                $table->integer('standing')->default(0);
                $table->timestamps();
                $table->unique(['character_id', 'see_id']);
            });
        }

        if (!Schema::hasTable('despair_states')) {
            Schema::create('despair_states', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('territory_id')->constrained('territories')->cascadeOnDelete();
                $table->unsignedTinyInteger('intensity')->default(0);
                $table->date('recorded_on');
                $table->timestamps();
                $table->unique(['territory_id']);
            });
        }

        if (!Schema::hasTable('game_events')) {
            Schema::create('game_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->string('event_key', 64);
                $table->string('title');
                $table->text('body');
                $table->string('status', 32)->default('scheduled');
                $table->date('due_on');
                $table->json('options');
                $table->string('chosen_option')->nullable();
                $table->json('effects')->nullable();
                $table->json('payload')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();
                $table->unique(['world_id', 'event_key']);
            });
        }

        if (!Schema::hasTable('armies')) {
            Schema::create('armies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->string('name');
                $table->string('kind', 32)->default('levy');
                $table->unsignedBigInteger('commander_character_id')->nullable();
                $table->unsignedBigInteger('owner_character_id')->nullable();
                $table->unsignedBigInteger('territory_id');
                $table->unsignedInteger('strength');
                $table->string('status', 32)->default('idle');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('battles')) {
            Schema::create('battles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->unsignedBigInteger('territory_id');
                $table->unsignedBigInteger('attacker_army_id');
                $table->unsignedBigInteger('defender_army_id');
                $table->string('kind', 32)->default('human');
                $table->unsignedInteger('attacker_strength_before');
                $table->unsignedInteger('defender_strength_before');
                $table->unsignedInteger('attacker_strength_after');
                $table->unsignedInteger('defender_strength_after');
                $table->string('winner', 32);
                $table->date('fought_on');
                $table->json('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('battles');
        Schema::dropIfExists('armies');
        Schema::dropIfExists('game_events');
        Schema::dropIfExists('despair_states');
        Schema::dropIfExists('church_relations');
        Schema::dropIfExists('territory_adjacencies');
    }
};
