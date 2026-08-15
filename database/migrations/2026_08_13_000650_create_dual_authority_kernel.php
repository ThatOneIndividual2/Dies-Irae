<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('regions')) {
            Schema::create('regions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->string('key', 128);
                $table->string('name');
                $table->string('map_key', 128)->nullable();
                $table->unsignedBigInteger('parent_region_id')->nullable();
                $table->timestamps();
                $table->unique(['world_id', 'key']);
                $table->foreign('parent_region_id')->references('id')->on('regions')->nullOnDelete();
            });
        }

        if (Schema::hasTable('territories') && !Schema::hasColumn('territories', 'key')) {
            Schema::table('territories', function (Blueprint $table) {
                $table->string('key', 128)->nullable()->after('name');
                $table->unsignedBigInteger('region_id')->nullable()->after('world_id');
                $table->unsignedBigInteger('parent_territory_id')->nullable();
                $table->string('territory_type', 32)->default('county');
                $table->string('terrain_type', 32)->nullable();
                $table->unsignedInteger('population')->default(0);
                $table->unsignedInteger('development')->default(0);
                $table->unsignedTinyInteger('control')->default(100);
                $table->unsignedBigInteger('owner_character_id')->nullable();
                $table->unsignedBigInteger('controller_character_id')->nullable();
                $table->string('supernatural_state', 32)->default('ordinary');
                $table->string('ruin_state', 32)->default('intact');
            });
        }

        if (!Schema::hasTable('holdings')) {
            Schema::create('holdings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('territory_id')->constrained('territories')->cascadeOnDelete();
                $table->string('key', 128);
                $table->string('name');
                $table->string('holding_type', 32);
                $table->unsignedInteger('development')->default(0);
                $table->unsignedInteger('fortification')->default(0);
                $table->unsignedInteger('base_tax')->default(0);
                $table->unsignedInteger('base_levy')->default(0);
                $table->unsignedBigInteger('owner_character_id')->nullable();
                $table->unsignedBigInteger('controller_character_id')->nullable();
                $table->timestamps();
                $table->unique(['world_id', 'key']);
            });
        }

        if (!Schema::hasTable('dynasties')) {
            Schema::create('dynasties', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->string('key', 128);
                $table->string('name');
                $table->unsignedBigInteger('founder_character_id')->nullable();
                $table->string('motto')->nullable();
                $table->integer('prestige')->default(0);
                $table->date('founded_date')->nullable();
                $table->date('extinct_date')->nullable();
                $table->timestamps();
                $table->unique(['world_id', 'key']);
            });
        }

        if (!Schema::hasTable('dynasty_houses')) {
            Schema::create('dynasty_houses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('dynasty_id')->constrained('dynasties')->cascadeOnDelete();
                $table->string('key', 128);
                $table->string('name');
                $table->unsignedBigInteger('parent_house_id')->nullable();
                $table->unsignedBigInteger('head_character_id')->nullable();
                $table->integer('prestige')->default(0);
                $table->timestamps();
                $table->unique(['world_id', 'key']);
                $table->foreign('parent_house_id')->references('id')->on('dynasty_houses')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('characters')) {
            Schema::create('characters', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->string('key', 128);
                $table->foreignId('dynasty_id')->nullable()->constrained('dynasties')->nullOnDelete();
                $table->foreignId('house_id')->nullable()->constrained('dynasty_houses')->nullOnDelete();
                $table->unsignedBigInteger('father_id')->nullable();
                $table->unsignedBigInteger('mother_id')->nullable();
                $table->unsignedBigInteger('spouse_id')->nullable();
                $table->string('first_name');
                $table->string('epithet')->nullable();
                $table->string('sex', 16);
                $table->date('birth_date');
                $table->date('death_date')->nullable();
                $table->boolean('is_alive')->default(true);
                $table->string('culture')->nullable();
                $table->unsignedBigInteger('faith_id')->nullable();
                $table->string('legitimacy_status', 32)->default('legitimate');
                $table->unsignedTinyInteger('health')->default(100);
                $table->unsignedTinyInteger('martial')->default(5);
                $table->unsignedTinyInteger('diplomacy')->default(5);
                $table->unsignedTinyInteger('stewardship')->default(5);
                $table->unsignedTinyInteger('intrigue')->default(5);
                $table->unsignedTinyInteger('learning')->default(5);
                $table->integer('prestige')->default(0);
                $table->timestamps();
                $table->unique(['world_id', 'key']);
                $table->foreign('father_id')->references('id')->on('characters')->nullOnDelete();
                $table->foreign('mother_id')->references('id')->on('characters')->nullOnDelete();
                $table->foreign('spouse_id')->references('id')->on('characters')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('succession_laws')) {
            Schema::create('succession_laws', function (Blueprint $table) {
                $table->id();
                $table->string('key')->unique();
                $table->string('name');
                $table->string('description')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('titles')) {
            Schema::create('titles', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->string('key', 128);
                $table->string('name');
                $table->string('adjective')->nullable();
                $table->string('rank', 32);
                $table->unsignedBigInteger('parent_title_id')->nullable();
                $table->unsignedBigInteger('de_jure_liege_title_id')->nullable();
                $table->unsignedBigInteger('capital_territory_id')->nullable();
                $table->unsignedBigInteger('primary_territory_id')->nullable();
                $table->foreignId('succession_law_id')->nullable()->constrained('succession_laws')->nullOnDelete();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_titular')->default(false);
                $table->timestamps();
                $table->unique(['world_id', 'key']);
                $table->foreign('parent_title_id')->references('id')->on('titles')->nullOnDelete();
                $table->foreign('de_jure_liege_title_id')->references('id')->on('titles')->nullOnDelete();
                $table->foreign('capital_territory_id')->references('id')->on('territories')->nullOnDelete();
                $table->foreign('primary_territory_id')->references('id')->on('territories')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('title_ownerships')) {
            Schema::create('title_ownerships', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('title_id')->constrained('titles')->cascadeOnDelete();
                $table->foreignId('holder_character_id')->constrained('characters')->cascadeOnDelete();
                $table->date('acquired_date');
                $table->date('lost_date')->nullable();
                $table->string('acquisition_type', 32)->default('grant');
                $table->boolean('is_current')->nullable();
                $table->timestamps();
                $table->unique(['title_id', 'is_current']);
            });
        }

        if (!Schema::hasTable('realms')) {
            Schema::create('realms', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->string('key', 128);
                $table->string('name');
                $table->foreignId('top_liege_character_id')->constrained('characters')->cascadeOnDelete();
                $table->foreignId('primary_title_id')->constrained('titles')->cascadeOnDelete();
                $table->unsignedInteger('realm_authority')->default(50);
                $table->integer('treasury')->default(0);
                $table->timestamps();
                $table->unique(['world_id', 'key']);
            });
        }

        if (!Schema::hasTable('feudal_contracts')) {
            Schema::create('feudal_contracts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->unsignedBigInteger('realm_id')->nullable();
                $table->foreignId('liege_character_id')->constrained('characters')->cascadeOnDelete();
                $table->foreignId('vassal_character_id')->constrained('characters')->cascadeOnDelete();
                $table->unsignedTinyInteger('tax_rate')->default(10);
                $table->unsignedTinyInteger('levy_rate')->default(50);
                $table->date('effective_date');
                $table->date('ended_date')->nullable();
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('vassal_relationships')) {
            Schema::create('vassal_relationships', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->unsignedBigInteger('realm_id')->nullable();
                $table->foreignId('liege_character_id')->constrained('characters')->cascadeOnDelete();
                $table->foreignId('vassal_character_id')->constrained('characters')->cascadeOnDelete();
                $table->unsignedBigInteger('primary_title_id')->nullable();
                $table->unsignedBigInteger('contract_id')->nullable();
                $table->date('started_date');
                $table->date('ended_date')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();
                $table->unique(['vassal_character_id', 'is_current'], 'vassal_rel_current_uq');
            });
        }

        if (!Schema::hasTable('faiths')) {
            Schema::create('faiths', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->string('key', 128);
                $table->string('name');
                $table->string('rite', 64)->nullable();
                $table->boolean('is_primary')->default(false);
                $table->timestamps();
                $table->unique(['world_id', 'key']);
            });
        }

        if (!Schema::hasTable('church_provinces')) {
            Schema::create('church_provinces', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('faith_id')->constrained('faiths')->cascadeOnDelete();
                $table->string('key', 128);
                $table->string('name');
                $table->unsignedBigInteger('metropolitan_see_id')->nullable();
                $table->timestamps();
                $table->unique(['world_id', 'key']);
            });
        }

        if (!Schema::hasTable('sees')) {
            Schema::create('sees', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->unsignedBigInteger('church_province_id')->nullable();
                $table->unsignedBigInteger('parent_see_id')->nullable();
                $table->unsignedBigInteger('territory_id')->nullable();
                $table->unsignedBigInteger('ordinary_office_id')->nullable();
                $table->unsignedBigInteger('seat_holding_id')->nullable();
                $table->string('key', 128);
                $table->string('name');
                $table->string('see_type', 32)->default('diocese');
                $table->boolean('is_active')->default(true);
                $table->boolean('is_exempt')->default(false);
                $table->timestamps();
                $table->unique(['world_id', 'key']);
                $table->foreign('church_province_id')->references('id')->on('church_provinces')->nullOnDelete();
                $table->foreign('parent_see_id')->references('id')->on('sees')->nullOnDelete();
                $table->foreign('territory_id')->references('id')->on('territories')->nullOnDelete();
                $table->foreign('seat_holding_id')->references('id')->on('holdings')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('spiritual_offices')) {
            Schema::create('spiritual_offices', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->string('key', 128);
                $table->string('name');
                $table->string('rank', 32);
                $table->string('appointment_mode', 32)->nullable();
                $table->unsignedBigInteger('see_id')->nullable();
                $table->unsignedBigInteger('church_province_id')->nullable();
                $table->unsignedBigInteger('monastery_id')->nullable();
                $table->unsignedBigInteger('religious_order_id')->nullable();
                $table->unsignedBigInteger('holy_order_id')->nullable();
                $table->boolean('is_active')->default(true);
                $table->boolean('is_papal_apex')->default(false);
                $table->timestamps();
                $table->unique(['world_id', 'key']);
                $table->foreign('see_id')->references('id')->on('sees')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('spiritual_office_holderships')) {
            Schema::create('spiritual_office_holderships', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('spiritual_office_id')->constrained('spiritual_offices')->cascadeOnDelete();
                $table->foreignId('holder_character_id')->constrained('characters')->cascadeOnDelete();
                $table->date('acquired_date');
                $table->date('lost_date')->nullable();
                $table->string('acquisition_type', 32)->default('appointment');
                $table->string('legitimacy', 32)->default('recognized');
                $table->unsignedBigInteger('appointed_by_character_id')->nullable();
                $table->unsignedBigInteger('previous_holdership_id')->nullable();
                $table->unsignedBigInteger('appointment_id')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();
                $table->unique(['spiritual_office_id', 'is_current'], 'spirit_office_current_uq');
                $table->foreign('appointed_by_character_id')->references('id')->on('characters')->nullOnDelete();
                $table->foreign('previous_holdership_id')->references('id')->on('spiritual_office_holderships')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('clergy_statuses')) {
            Schema::create('clergy_statuses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
                $table->string('status', 32);
                $table->string('orders_grade', 32)->nullable();
                $table->string('religious_state', 32)->default('none');
                $table->string('regularity', 32)->default('regular');
                $table->unsignedBigInteger('religious_order_id')->nullable();
                $table->unsignedBigInteger('monastery_id')->nullable();
                $table->date('started_date');
                $table->date('ended_date')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();
                $table->unique(['character_id', 'is_current'], 'clergy_status_current_uq');
            });
        }

        if (!Schema::hasTable('religious_orders')) {
            Schema::create('religious_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('faith_id')->constrained('faiths')->cascadeOnDelete();
                $table->string('key', 128);
                $table->string('name');
                $table->string('kind', 32)->default('monastic');
                $table->string('rule_key', 64)->nullable();
                $table->string('sanction_status', 32)->default('unsanctioned');
                $table->date('sanctioned_date')->nullable();
                $table->date('suppressed_date')->nullable();
                $table->timestamps();
                $table->unique(['world_id', 'key']);
            });
        }

        if (!Schema::hasTable('monasteries')) {
            Schema::create('monasteries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('holding_id')->constrained('holdings')->cascadeOnDelete();
                $table->foreignId('faith_id')->constrained('faiths')->cascadeOnDelete();
                $table->unsignedBigInteger('religious_order_id')->nullable();
                $table->unsignedBigInteger('abbot_office_id')->nullable();
                $table->string('key', 128);
                $table->string('name');
                $table->string('rule', 64)->nullable();
                $table->boolean('is_exempt')->default(false);
                $table->boolean('is_active')->default(true);
                $table->unsignedInteger('religious_population')->default(0);
                $table->timestamps();
                $table->unique(['world_id', 'key']);
                $table->unique('holding_id');
                $table->foreign('religious_order_id')->references('id')->on('religious_orders')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('holy_orders')) {
            Schema::create('holy_orders', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('faith_id')->constrained('faiths')->cascadeOnDelete();
                $table->unsignedBigInteger('religious_order_id')->nullable();
                $table->unsignedBigInteger('grand_master_office_id')->nullable();
                $table->string('key', 128);
                $table->string('name');
                $table->string('sanction_status', 32)->default('unsanctioned');
                $table->boolean('papal_protection')->default(false);
                $table->date('sanctioned_date')->nullable();
                $table->date('suppressed_date')->nullable();
                $table->timestamps();
                $table->unique(['world_id', 'key']);
                $table->foreign('religious_order_id')->references('id')->on('religious_orders')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('see_territories')) {
            Schema::create('see_territories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('see_id')->constrained('sees')->cascadeOnDelete();
                $table->foreignId('territory_id')->constrained('territories')->cascadeOnDelete();
                $table->timestamps();
                $table->unique(['see_id', 'territory_id']);
            });
        }

        if (!Schema::hasTable('papacies')) {
            Schema::create('papacies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('faith_id')->constrained('faiths')->cascadeOnDelete();
                $table->foreignId('papal_see_id')->constrained('sees')->cascadeOnDelete();
                $table->foreignId('papal_office_id')->constrained('spiritual_offices')->cascadeOnDelete();
                $table->string('status', 32)->default('vacant');
                $table->unsignedBigInteger('recognized_claim_id')->nullable();
                $table->timestamps();
                $table->unique('world_id');
            });
        }

        if (!Schema::hasTable('papal_claims')) {
            Schema::create('papal_claims', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('papacy_id')->constrained('papacies')->cascadeOnDelete();
                $table->foreignId('claimant_character_id')->constrained('characters')->cascadeOnDelete();
                $table->unsignedBigInteger('claimant_office_id')->nullable();
                $table->string('status', 32)->default('pending');
                $table->date('claimed_date');
                $table->date('ended_date')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();
                $table->foreign('claimant_office_id')->references('id')->on('spiritual_offices')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('clergy_appointments')) {
            Schema::create('clergy_appointments', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('spiritual_office_id')->constrained('spiritual_offices')->cascadeOnDelete();
                $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
                $table->string('mode', 32);
                $table->string('status', 32)->default('pending');
                $table->unsignedBigInteger('appointed_by_character_id')->nullable();
                $table->unsignedBigInteger('invested_by_character_id')->nullable();
                $table->unsignedBigInteger('competing_appointment_id')->nullable();
                $table->string('policy_key', 64)->nullable();
                $table->date('appointed_date');
                $table->date('resolved_date')->nullable();
                $table->timestamps();
                $table->foreign('appointed_by_character_id')->references('id')->on('characters')->nullOnDelete();
                $table->foreign('invested_by_character_id')->references('id')->on('characters')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('investiture_rights')) {
            Schema::create('investiture_rights', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('see_id')->constrained('sees')->cascadeOnDelete();
                $table->unsignedBigInteger('nominator_character_id')->nullable();
                $table->unsignedBigInteger('nominator_title_id')->nullable();
                $table->string('mode', 32)->default('papal');
                $table->date('started_date');
                $table->date('ended_date')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();
                $table->foreign('nominator_character_id')->references('id')->on('characters')->nullOnDelete();
                $table->foreign('nominator_title_id')->references('id')->on('titles')->nullOnDelete();
                $table->unique(['see_id', 'is_current'], 'investiture_see_current_uq');
            });
        }

        if (!Schema::hasTable('excommunication_states')) {
            Schema::create('excommunication_states', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
                $table->unsignedBigInteger('issued_by_character_id')->nullable();
                $table->unsignedBigInteger('issuing_office_id')->nullable();
                $table->string('reason')->nullable();
                $table->date('issued_date');
                $table->date('lifted_date')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();
                $table->foreign('issued_by_character_id')->references('id')->on('characters')->nullOnDelete();
                $table->foreign('issuing_office_id')->references('id')->on('spiritual_offices')->nullOnDelete();
                $table->unique(['character_id', 'is_current'], 'excommunication_current_uq');
            });
        }

        if (!Schema::hasTable('interdict_states')) {
            Schema::create('interdict_states', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->string('target_type', 32);
                $table->unsignedBigInteger('see_id')->nullable();
                $table->unsignedBigInteger('territory_id')->nullable();
                $table->unsignedBigInteger('issued_by_character_id')->nullable();
                $table->unsignedBigInteger('issuing_office_id')->nullable();
                $table->string('reason')->nullable();
                $table->date('issued_date');
                $table->date('lifted_date')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();
                $table->foreign('see_id')->references('id')->on('sees')->nullOnDelete();
                $table->foreign('territory_id')->references('id')->on('territories')->nullOnDelete();
                $table->foreign('issued_by_character_id')->references('id')->on('characters')->nullOnDelete();
                $table->foreign('issuing_office_id')->references('id')->on('spiritual_offices')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('heresies')) {
            Schema::create('heresies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('faith_id')->constrained('faiths')->cascadeOnDelete();
                $table->string('key', 128);
                $table->string('name');
                $table->string('judgement', 32)->default('named');
                $table->unsignedBigInteger('condemned_by_office_id')->nullable();
                $table->date('condemned_date')->nullable();
                $table->timestamps();
                $table->unique(['world_id', 'key']);
                $table->foreign('condemned_by_office_id')->references('id')->on('spiritual_offices')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('heresy_presences')) {
            Schema::create('heresy_presences', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('heresy_id')->constrained('heresies')->cascadeOnDelete();
                $table->unsignedBigInteger('character_id')->nullable();
                $table->unsignedBigInteger('territory_id')->nullable();
                $table->string('status', 32)->default('suspected');
                $table->date('started_date');
                $table->date('ended_date')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();
                $table->foreign('character_id')->references('id')->on('characters')->nullOnDelete();
                $table->foreign('territory_id')->references('id')->on('territories')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('sacramental_authorities')) {
            Schema::create('sacramental_authorities', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
                $table->string('sacrament_kind', 32);
                $table->string('grant_source', 32);
                $table->unsignedBigInteger('authorizing_office_id')->nullable();
                $table->date('granted_date');
                $table->date('revoked_date')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();
                $table->foreign('authorizing_office_id')->references('id')->on('spiritual_offices')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('saints')) {
            Schema::create('saints', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->unsignedBigInteger('character_id')->nullable();
                $table->unsignedBigInteger('recognized_by_office_id')->nullable();
                $table->unsignedBigInteger('recognized_by_character_id')->nullable();
                $table->string('key', 128);
                $table->string('name');
                $table->string('recognition_status', 32)->default('cultus');
                $table->date('recognized_date')->nullable();
                $table->timestamps();
                $table->unique(['world_id', 'key']);
                $table->foreign('character_id')->references('id')->on('characters')->nullOnDelete();
                $table->foreign('recognized_by_office_id')->references('id')->on('spiritual_offices')->nullOnDelete();
                $table->foreign('recognized_by_character_id')->references('id')->on('characters')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('relics')) {
            Schema::create('relics', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->unsignedBigInteger('saint_id')->nullable();
                $table->string('key', 128);
                $table->string('name');
                $table->string('authenticity', 32)->default('unrecognized');
                $table->unsignedBigInteger('current_holding_id')->nullable();
                $table->unsignedBigInteger('current_territory_id')->nullable();
                $table->unsignedBigInteger('current_character_id')->nullable();
                $table->timestamps();
                $table->unique(['world_id', 'key']);
                $table->foreign('saint_id')->references('id')->on('saints')->nullOnDelete();
                $table->foreign('current_holding_id')->references('id')->on('holdings')->nullOnDelete();
                $table->foreign('current_territory_id')->references('id')->on('territories')->nullOnDelete();
                $table->foreign('current_character_id')->references('id')->on('characters')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('relic_custodies')) {
            Schema::create('relic_custodies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('relic_id')->constrained('relics')->cascadeOnDelete();
                $table->unsignedBigInteger('holding_id')->nullable();
                $table->unsignedBigInteger('territory_id')->nullable();
                $table->unsignedBigInteger('character_id')->nullable();
                $table->date('acquired_date');
                $table->date('lost_date')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();
                $table->unique(['relic_id', 'is_current'], 'relic_custody_current_uq');
            });
        }

        if (!Schema::hasTable('exorcist_legitimacies')) {
            Schema::create('exorcist_legitimacies', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
                $table->unsignedBigInteger('granted_by_character_id')->nullable();
                $table->unsignedBigInteger('granted_by_office_id')->nullable();
                $table->string('scope', 32)->default('general');
                $table->date('granted_date');
                $table->date('revoked_date')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();
                $table->unique(['character_id', 'is_current'], 'exorcist_legit_current_uq');
            });
        }

        if (!Schema::hasTable('extraordinary_spiritual_authorizations')) {
            Schema::create('extraordinary_spiritual_authorizations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
                $table->string('action_kind', 64);
                $table->unsignedBigInteger('granted_by_character_id')->nullable();
                $table->unsignedBigInteger('granted_by_office_id')->nullable();
                $table->date('granted_date');
                $table->date('expires_date')->nullable();
                $table->date('revoked_date')->nullable();
                $table->boolean('is_current')->nullable();
                $table->timestamps();
            });
        }

        Schema::table('spiritual_offices', function (Blueprint $table) {
            if (!Schema::hasColumn('spiritual_offices', 'monastery_id')) {
                return;
            }
        });

        try {
            Schema::table('sees', function (Blueprint $table) {
                $table->foreign('ordinary_office_id')->references('id')->on('spiritual_offices')->nullOnDelete();
            });
        } catch (\Throwable $e) {
        }

        try {
            Schema::table('church_provinces', function (Blueprint $table) {
                $table->foreign('metropolitan_see_id')->references('id')->on('sees')->nullOnDelete();
            });
        } catch (\Throwable $e) {
        }

        try {
            Schema::table('monasteries', function (Blueprint $table) {
                $table->foreign('abbot_office_id')->references('id')->on('spiritual_offices')->nullOnDelete();
            });
        } catch (\Throwable $e) {
        }

        try {
            Schema::table('spiritual_offices', function (Blueprint $table) {
                $table->foreign('monastery_id')->references('id')->on('monasteries')->nullOnDelete();
                $table->foreign('religious_order_id')->references('id')->on('religious_orders')->nullOnDelete();
                $table->foreign('holy_order_id')->references('id')->on('holy_orders')->nullOnDelete();
            });
        } catch (\Throwable $e) {
        }

        try {
            Schema::table('papacies', function (Blueprint $table) {
                $table->foreign('recognized_claim_id')->references('id')->on('papal_claims')->nullOnDelete();
            });
        } catch (\Throwable $e) {
        }

        try {
            Schema::table('characters', function (Blueprint $table) {
                $table->foreign('faith_id')->references('id')->on('faiths')->nullOnDelete();
            });
        } catch (\Throwable $e) {
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('extraordinary_spiritual_authorizations');
        Schema::dropIfExists('exorcist_legitimacies');
        Schema::dropIfExists('relic_custodies');
        Schema::dropIfExists('relics');
        Schema::dropIfExists('saints');
        Schema::dropIfExists('sacramental_authorities');
        Schema::dropIfExists('heresy_presences');
        Schema::dropIfExists('heresies');
        Schema::dropIfExists('interdict_states');
        Schema::dropIfExists('excommunication_states');
        Schema::dropIfExists('investiture_rights');
        Schema::dropIfExists('clergy_appointments');
        Schema::dropIfExists('papal_claims');
        Schema::dropIfExists('papacies');
        Schema::dropIfExists('see_territories');
        Schema::dropIfExists('holy_orders');
        Schema::dropIfExists('monasteries');
        Schema::dropIfExists('religious_orders');
        Schema::dropIfExists('clergy_statuses');
        Schema::dropIfExists('spiritual_office_holderships');
        Schema::dropIfExists('spiritual_offices');
        Schema::dropIfExists('sees');
        Schema::dropIfExists('church_provinces');
        Schema::dropIfExists('faiths');
        Schema::dropIfExists('vassal_relationships');
        Schema::dropIfExists('feudal_contracts');
        Schema::dropIfExists('realms');
        Schema::dropIfExists('title_ownerships');
        Schema::dropIfExists('titles');
        Schema::dropIfExists('succession_laws');
        Schema::dropIfExists('characters');
        Schema::dropIfExists('dynasty_houses');
        Schema::dropIfExists('dynasties');
        Schema::dropIfExists('holdings');
        Schema::dropIfExists('regions');
    }
};
