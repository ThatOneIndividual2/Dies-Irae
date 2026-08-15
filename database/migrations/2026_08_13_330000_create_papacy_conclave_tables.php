<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('papacies')) {
            Schema::table('papacies', function (Blueprint $table) {
                if (! Schema::hasColumn('papacies', 'church_legitimacy_bp')) {
                    $table->unsignedInteger('church_legitimacy_bp')->default(8000);
                }
                if (! Schema::hasColumn('papacies', 'rival_legitimacy_bp')) {
                    $table->unsignedInteger('rival_legitimacy_bp')->default(0);
                }
                if (! Schema::hasColumn('papacies', 'vacancy_opened_date')) {
                    $table->date('vacancy_opened_date')->nullable();
                }
                if (! Schema::hasColumn('papacies', 'last_enthroned_date')) {
                    $table->date('last_enthroned_date')->nullable();
                }
            });
        }

        if (! Schema::hasTable('cardinal_profiles')) {
            Schema::create('cardinal_profiles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('world_id');
                $table->unsignedBigInteger('character_id');
                $table->unsignedBigInteger('spiritual_office_id')->nullable();
                $table->string('theology', 32)->default('conservative');
                $table->string('faction', 32)->default('curial');
                $table->unsignedTinyInteger('ambition')->default(40);
                $table->unsignedTinyInteger('fear')->default(20);
                $table->unsignedTinyInteger('corruption')->default(20);
                $table->unsignedTinyInteger('theological_reputation')->default(50);
                $table->string('realm_tie', 64)->nullable();
                $table->json('relationships')->nullable();
                $table->json('preferences')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();

                $table->unique(['character_id', 'is_current'], 'cardinal_profile_current_uq');
                $table->index(['world_id']);
            });
        }

        if (! Schema::hasTable('conclave_sessions')) {
            Schema::create('conclave_sessions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('world_id');
                $table->unsignedBigInteger('papacy_id');
                $table->string('phase', 32)->default('vacant');
                $table->string('papal_see_key', 64)->default('rome');
                $table->string('seat_key', 64)->default('rome');
                $table->string('seat_name', 128)->default('Rome');
                $table->boolean('seat_relocated')->default(false);
                $table->unsignedSmallInteger('round')->default(0);
                $table->unsignedSmallInteger('deadlock_rounds')->default(0);
                $table->unsignedSmallInteger('assembly_delay_remaining')->default(0);
                $table->unsignedBigInteger('elected_character_id')->nullable();
                $table->unsignedBigInteger('accepted_character_id')->nullable();
                $table->unsignedBigInteger('enthroned_character_id')->nullable();
                $table->unsignedBigInteger('compromise_character_id')->nullable();
                $table->unsignedBigInteger('contested_by_character_id')->nullable();
                $table->json('extraordinary_rules')->nullable();
                $table->string('vacancy_cause', 64)->nullable();
                $table->date('opened_date')->nullable();
                $table->date('enthroned_date')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();

                $table->unique(['papacy_id', 'is_current'], 'conclave_session_current_uq');
                $table->index(['world_id', 'phase']);
            });
        }

        if (! Schema::hasTable('conclave_electors')) {
            Schema::create('conclave_electors', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('world_id');
                $table->unsignedBigInteger('conclave_session_id');
                $table->unsignedBigInteger('character_id');
                $table->boolean('alive')->default(true);
                $table->boolean('accessible')->default(true);
                $table->boolean('present')->default(false);
                $table->boolean('is_extraordinary')->default(false);
                $table->string('current_vote_key', 64)->nullable();
                $table->string('first_preference_key', 64)->nullable();
                $table->unsignedInteger('patronage_received')->default(0);
                $table->unsignedInteger('threat_received')->default(0);
                $table->timestamps();

                $table->unique(['conclave_session_id', 'character_id'], 'conclave_elector_uq');
                $table->index(['world_id']);
            });
        }

        if (! Schema::hasTable('conclave_ballots')) {
            Schema::create('conclave_ballots', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('world_id');
                $table->unsignedBigInteger('conclave_session_id');
                $table->unsignedSmallInteger('round');
                $table->string('phase', 32);
                $table->unsignedSmallInteger('present')->default(0);
                $table->unsignedSmallInteger('needed')->default(0);
                $table->unsignedBigInteger('leader_character_id')->nullable();
                $table->boolean('majority')->default(false);
                $table->boolean('deadlocked')->default(false);
                $table->json('votes')->nullable();
                $table->json('tally')->nullable();
                $table->timestamps();

                $table->unique(['conclave_session_id', 'round'], 'conclave_ballot_round_uq');
                $table->index(['world_id']);
            });
        }

        if (! Schema::hasTable('secular_papal_pressures')) {
            Schema::create('secular_papal_pressures', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('world_id');
                $table->unsignedBigInteger('papacy_id');
                $table->unsignedBigInteger('conclave_session_id')->nullable();
                $table->unsignedBigInteger('ruler_character_id');
                $table->string('ruler_realm_key', 64)->nullable();
                $table->string('kind', 32);
                $table->unsignedBigInteger('candidate_character_id')->nullable();
                $table->json('target_character_ids')->nullable();
                $table->unsignedSmallInteger('magnitude')->default(0);
                $table->date('pressure_date');
                $table->timestamps();

                $table->index(['world_id', 'papacy_id']);
            });
        }

        if (! Schema::hasTable('papal_obediences')) {
            Schema::create('papal_obediences', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('world_id');
                $table->unsignedBigInteger('papacy_id');
                $table->unsignedBigInteger('papal_claim_id')->nullable();
                $table->string('subject_kind', 32);
                $table->string('subject_key', 64);
                $table->unsignedBigInteger('claimant_character_id');
                $table->date('pledged_date');
                $table->date('ended_date')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();

                $table->unique(['papacy_id', 'subject_kind', 'subject_key', 'is_current'], 'papal_obedience_current_uq');
                $table->index(['world_id', 'claimant_character_id']);
            });
        }

        if (! Schema::hasTable('church_legitimacy_states')) {
            Schema::create('church_legitimacy_states', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('world_id');
                $table->unsignedBigInteger('papacy_id');
                $table->unsignedInteger('recognized_bp')->default(8000);
                $table->unsignedInteger('rival_bp')->default(0);
                $table->date('as_of_date')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();

                $table->unique(['papacy_id', 'is_current'], 'church_legitimacy_current_uq');
                $table->index(['world_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('church_legitimacy_states');
        Schema::dropIfExists('papal_obediences');
        Schema::dropIfExists('secular_papal_pressures');
        Schema::dropIfExists('conclave_ballots');
        Schema::dropIfExists('conclave_electors');
        Schema::dropIfExists('conclave_sessions');
        Schema::dropIfExists('cardinal_profiles');
    }
};
