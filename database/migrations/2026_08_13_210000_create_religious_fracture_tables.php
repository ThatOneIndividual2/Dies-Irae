<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('religious_movements')) {
            Schema::create('religious_movements', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('faith_id')->nullable()->constrained('faiths')->nullOnDelete();
                $table->string('key', 128);
                $table->string('name');
                $table->string('kind', 32);
                $table->string('visibility', 32)->default('secret');
                $table->unsignedTinyInteger('radicalization')->default(0);
                $table->unsignedBigInteger('founder_character_id')->nullable();
                $table->unsignedBigInteger('leader_character_id')->nullable();
                $table->unsignedBigInteger('origin_territory_id')->nullable();
                $table->string('status', 32)->default('active');
                $table->date('founded_date');
                $table->date('ended_date')->nullable();
                $table->timestamps();

                $table->unique(['world_id', 'key']);
                $table->index(['world_id', 'kind', 'status']);
                $table->foreign('founder_character_id')->references('id')->on('characters')->nullOnDelete();
                $table->foreign('leader_character_id')->references('id')->on('characters')->nullOnDelete();
                $table->foreign('origin_territory_id')->references('id')->on('territories')->nullOnDelete();
            });
        }

        if (Schema::hasTable('heresies') && !Schema::hasColumn('heresies', 'movement_id')) {
            Schema::table('heresies', function (Blueprint $table) {
                $table->unsignedBigInteger('movement_id')->nullable()->after('faith_id');
                $table->string('doctrine_error_key', 64)->nullable()->after('name');
                $table->foreign('movement_id')->references('id')->on('religious_movements')->nullOnDelete();
            });
        }

        if (Schema::hasTable('heresy_presences') && !Schema::hasColumn('heresy_presences', 'intensity')) {
            Schema::table('heresy_presences', function (Blueprint $table) {
                $table->unsignedTinyInteger('intensity')->default(1);
            });
        }

        if (!Schema::hasTable('apostasy_states')) {
            Schema::create('apostasy_states', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('movement_id')->nullable()->constrained('religious_movements')->nullOnDelete();
                $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
                $table->string('from_faith_key', 64)->nullable();
                $table->string('cause', 32)->default('renunciation');
                $table->date('apostasized_date');
                $table->date('reconciled_date')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();

                $table->unique(['character_id', 'is_current'], 'apostasy_current_uq');
            });
        }

        if (!Schema::hasTable('popular_movement_states')) {
            Schema::create('popular_movement_states', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('movement_id')->constrained('religious_movements')->cascadeOnDelete();
                $table->string('orthodoxy_stance', 32)->default('reform');
                $table->unsignedTinyInteger('fervor')->default(10);
                $table->unsignedTinyInteger('violence')->default(0);
                $table->boolean('church_regularized')->default(false);
                $table->timestamps();

                $table->unique('movement_id');
            });
        }

        if (!Schema::hasTable('anti_clerical_unrests')) {
            Schema::create('anti_clerical_unrests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('movement_id')->constrained('religious_movements')->cascadeOnDelete();
                $table->foreignId('territory_id')->constrained('territories')->cascadeOnDelete();
                $table->unsignedTinyInteger('intensity')->default(10);
                $table->unsignedInteger('clergy_assaults')->default(0);
                $table->unsignedInteger('property_attacks')->default(0);
                $table->string('status', 32)->default('open');
                $table->date('started_date');
                $table->date('ended_date')->nullable();
                $table->timestamps();

                $table->index(['world_id', 'territory_id']);
            });
        }

        if (!Schema::hasTable('false_prophet_states')) {
            Schema::create('false_prophet_states', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('movement_id')->constrained('religious_movements')->cascadeOnDelete();
                $table->foreignId('prophet_character_id')->constrained('characters')->cascadeOnDelete();
                $table->string('claimed_revelation', 128)->nullable();
                $table->unsignedInteger('following')->default(0);
                $table->string('examination', 32)->default('unexamined');
                $table->timestamps();

                $table->unique('movement_id');
            });
        }

        if (!Schema::hasTable('schisms')) {
            Schema::create('schisms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('movement_id')->constrained('religious_movements')->cascadeOnDelete();
                $table->foreignId('papacy_id')->nullable()->constrained('papacies')->nullOnDelete();
                $table->unsignedBigInteger('recognized_claim_id')->nullable();
                $table->unsignedBigInteger('rival_claim_id')->nullable();
                $table->string('status', 32)->default('open');
                $table->date('opened_date');
                $table->date('healed_date')->nullable();
                $table->timestamps();

                $table->unique('movement_id');
                $table->foreign('recognized_claim_id')->references('id')->on('papal_claims')->nullOnDelete();
                $table->foreign('rival_claim_id')->references('id')->on('papal_claims')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('schism_obediences')) {
            Schema::create('schism_obediences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('schism_id')->constrained('schisms')->cascadeOnDelete();
                $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
                $table->foreignId('papal_claim_id')->constrained('papal_claims')->cascadeOnDelete();
                $table->date('pledged_date');
                $table->date('ended_date')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();

                $table->unique(['character_id', 'is_current'], 'schism_obedience_current_uq');
            });
        }

        if (!Schema::hasTable('schism_see_allegiances')) {
            Schema::create('schism_see_allegiances', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('schism_id')->constrained('schisms')->cascadeOnDelete();
                $table->foreignId('see_id')->constrained('sees')->cascadeOnDelete();
                $table->foreignId('papal_claim_id')->constrained('papal_claims')->cascadeOnDelete();
                $table->date('pledged_date');
                $table->date('ended_date')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();

                $table->unique(['see_id', 'is_current'], 'schism_see_current_uq');
            });
        }

        if (!Schema::hasTable('schism_secular_backers')) {
            Schema::create('schism_secular_backers', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('schism_id')->constrained('schisms')->cascadeOnDelete();
                $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
                $table->foreignId('papal_claim_id')->constrained('papal_claims')->cascadeOnDelete();
                $table->unsignedBigInteger('title_id')->nullable();
                $table->date('backed_date');
                $table->date('ended_date')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();

                $table->foreign('title_id')->references('id')->on('titles')->nullOnDelete();
                $table->unique(['character_id', 'is_current'], 'schism_backer_current_uq');
            });
        }

        if (!Schema::hasTable('cult_organizations')) {
            Schema::create('cult_organizations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('movement_id')->constrained('religious_movements')->cascadeOnDelete();
                $table->unsignedBigInteger('faction_id')->nullable();
                $table->foreignId('leader_character_id')->nullable()->constrained('characters')->nullOnDelete();
                $table->string('kind', 32);
                $table->unsignedTinyInteger('secrecy')->default(70);
                $table->string('discovery_state', 32)->default('hidden');
                $table->string('hidden_objective', 128)->nullable();
                $table->boolean('infiltrating_church')->default(false);
                $table->timestamps();

                $table->unique('movement_id');
                $table->foreign('faction_id')->references('id')->on('demonic_factions')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('cult_cells')) {
            Schema::create('cult_cells', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('organization_id')->constrained('cult_organizations')->cascadeOnDelete();
                $table->foreignId('territory_id')->constrained('territories')->cascadeOnDelete();
                $table->foreignId('leader_character_id')->nullable()->constrained('characters')->nullOnDelete();
                $table->unsignedTinyInteger('strength')->default(10);
                $table->unsignedTinyInteger('secrecy')->default(70);
                $table->string('discovery_state', 32)->default('hidden');
                $table->boolean('is_current')->nullable();
                $table->timestamps();

                $table->index(['organization_id', 'territory_id']);
            });
        }

        if (!Schema::hasTable('cult_members')) {
            Schema::create('cult_members', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('organization_id')->constrained('cult_organizations')->cascadeOnDelete();
                $table->unsignedBigInteger('cell_id')->nullable();
                $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
                $table->string('role', 32)->default('initiate');
                $table->date('recruited_date');
                $table->date('left_date')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();

                $table->foreign('cell_id')->references('id')->on('cult_cells')->nullOnDelete();
                $table->unique(['character_id', 'is_current'], 'cult_member_current_uq');
            });
        }

        if (!Schema::hasTable('cult_rituals')) {
            Schema::create('cult_rituals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('organization_id')->constrained('cult_organizations')->cascadeOnDelete();
                $table->unsignedBigInteger('cell_id')->nullable();
                $table->string('rite_key', 64);
                $table->boolean('requires_sacrifice')->default(false);
                $table->date('performed_date');
                $table->unsignedTinyInteger('corruption_delta')->default(0);
                $table->timestamps();

                $table->foreign('cell_id')->references('id')->on('cult_cells')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('cult_infiltrations')) {
            Schema::create('cult_infiltrations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('organization_id')->constrained('cult_organizations')->cascadeOnDelete();
                $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
                $table->string('target_type', 32);
                $table->unsignedBigInteger('target_id')->nullable();
                $table->unsignedTinyInteger('depth')->default(1);
                $table->date('started_date');
                $table->date('ended_date')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('cult_objectives')) {
            Schema::create('cult_objectives', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('organization_id')->constrained('cult_organizations')->cascadeOnDelete();
                $table->string('objective_key', 64);
                $table->string('status', 32)->default('hidden');
                $table->timestamps();
            });
        }

        if (Schema::hasTable('cults')) {
            if (Schema::hasColumn('cults', 'faction_id')) {
                try {
                    Schema::table('cults', function (Blueprint $table) {
                        $table->dropForeign(['faction_id']);
                    });
                } catch (\Throwable $e) {
                }
                DB::statement('ALTER TABLE cults MODIFY faction_id BIGINT UNSIGNED NULL');
                try {
                    Schema::table('cults', function (Blueprint $table) {
                        $table->foreign('faction_id')->references('id')->on('demonic_factions')->nullOnDelete();
                    });
                } catch (\Throwable $e) {
                }
            }

            Schema::table('cults', function (Blueprint $table) {
                if (!Schema::hasColumn('cults', 'organization_id')) {
                    $table->unsignedBigInteger('organization_id')->nullable();
                }
                if (!Schema::hasColumn('cults', 'kind')) {
                    $table->string('kind', 32)->default('demonic');
                }
                if (!Schema::hasColumn('cults', 'discovery_state')) {
                    $table->string('discovery_state', 32)->nullable();
                }
            });

            try {
                Schema::table('cults', function (Blueprint $table) {
                    $table->foreign('organization_id')->references('id')->on('cult_organizations')->nullOnDelete();
                });
            } catch (\Throwable $e) {
            }
        }

        if (!Schema::hasTable('movement_presences')) {
            Schema::create('movement_presences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('movement_id')->constrained('religious_movements')->cascadeOnDelete();
                $table->foreignId('territory_id')->constrained('territories')->cascadeOnDelete();
                $table->unsignedTinyInteger('intensity')->default(10);
                $table->string('visibility', 32)->default('secret');
                $table->unsignedTinyInteger('clergy_adherents')->default(0);
                $table->unsignedInteger('popular_adherents')->default(0);
                $table->date('started_date');
                $table->date('ended_date')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();

                $table->unique(['movement_id', 'territory_id', 'is_current'], 'movement_presence_current_uq');
            });
        }

        if (!Schema::hasTable('movement_adherents')) {
            Schema::create('movement_adherents', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('movement_id')->constrained('religious_movements')->cascadeOnDelete();
                $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
                $table->string('role', 32)->default('adherent');
                $table->boolean('is_clergy')->default(false);
                $table->boolean('is_patron')->default(false);
                $table->date('joined_date');
                $table->date('left_date')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();

                $table->unique(['character_id', 'movement_id', 'is_current'], 'movement_adherent_current_uq');
            });
        }

        if (!Schema::hasTable('movement_spread_events')) {
            Schema::create('movement_spread_events', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('movement_id')->constrained('religious_movements')->cascadeOnDelete();
                $table->foreignId('from_territory_id')->constrained('territories')->cascadeOnDelete();
                $table->foreignId('to_territory_id')->constrained('territories')->cascadeOnDelete();
                $table->string('vector', 32);
                $table->unsignedTinyInteger('magnitude')->default(1);
                $table->date('spread_date');
                $table->timestamps();

                $table->index(['movement_id', 'vector']);
            });
        }

        if (!Schema::hasTable('fracture_detections')) {
            Schema::create('fracture_detections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('movement_id')->constrained('religious_movements')->cascadeOnDelete();
                $table->unsignedBigInteger('territory_id')->nullable();
                $table->unsignedBigInteger('accused_character_id')->nullable();
                $table->unsignedBigInteger('reporter_character_id')->nullable();
                $table->string('source', 32);
                $table->string('outcome', 32)->default('open');
                $table->boolean('public_reveal')->default(false);
                $table->boolean('sealed_confession')->default(false);
                $table->date('detected_date');
                $table->timestamps();

                $table->foreign('territory_id')->references('id')->on('territories')->nullOnDelete();
                $table->foreign('accused_character_id')->references('id')->on('characters')->nullOnDelete();
                $table->foreign('reporter_character_id')->references('id')->on('characters')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('church_fracture_responses')) {
            Schema::create('church_fracture_responses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('movement_id')->constrained('religious_movements')->cascadeOnDelete();
                $table->foreignId('actor_character_id')->constrained('characters')->cascadeOnDelete();
                $table->unsignedBigInteger('office_id')->nullable();
                $table->string('response', 32);
                $table->string('result', 32)->default('recorded');
                $table->unsignedTinyInteger('radicalization_delta')->default(0);
                $table->date('response_date');
                $table->json('payload')->nullable();
                $table->timestamps();

                $table->foreign('office_id')->references('id')->on('spiritual_offices')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('secular_fracture_responses')) {
            Schema::create('secular_fracture_responses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('movement_id')->constrained('religious_movements')->cascadeOnDelete();
                $table->foreignId('actor_character_id')->constrained('characters')->cascadeOnDelete();
                $table->unsignedBigInteger('title_id')->nullable();
                $table->string('response', 32);
                $table->string('result', 32)->default('recorded');
                $table->unsignedTinyInteger('radicalization_delta')->default(0);
                $table->date('response_date');
                $table->json('payload')->nullable();
                $table->timestamps();

                $table->foreign('title_id')->references('id')->on('titles')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('secular_fracture_responses');
        Schema::dropIfExists('church_fracture_responses');
        Schema::dropIfExists('fracture_detections');
        Schema::dropIfExists('movement_spread_events');
        Schema::dropIfExists('movement_adherents');
        Schema::dropIfExists('movement_presences');
        Schema::dropIfExists('cult_objectives');
        Schema::dropIfExists('cult_infiltrations');
        Schema::dropIfExists('cult_rituals');
        Schema::dropIfExists('cult_members');
        Schema::dropIfExists('cult_cells');
        Schema::dropIfExists('cult_organizations');
        Schema::dropIfExists('schism_secular_backers');
        Schema::dropIfExists('schism_see_allegiances');
        Schema::dropIfExists('schism_obediences');
        Schema::dropIfExists('schisms');
        Schema::dropIfExists('false_prophet_states');
        Schema::dropIfExists('anti_clerical_unrests');
        Schema::dropIfExists('popular_movement_states');
        Schema::dropIfExists('apostasy_states');
        Schema::dropIfExists('religious_movements');
    }
};
