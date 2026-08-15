<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive spiritual-state tables. Does not drop catastrophe corruption_states
 * or hell demonic_influences; those are altered in place.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->createIfMissing('character_spiritual_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->unsignedTinyInteger('faith')->default(40);
            $table->unsignedTinyInteger('hope')->default(40);
            $table->unsignedTinyInteger('charity')->default(40);
            $table->unsignedTinyInteger('pride')->default(20);
            $table->unsignedTinyInteger('greed')->default(20);
            $table->unsignedTinyInteger('lust')->default(20);
            $table->unsignedTinyInteger('envy')->default(20);
            $table->unsignedTinyInteger('gluttony')->default(20);
            $table->unsignedTinyInteger('wrath')->default(20);
            $table->unsignedTinyInteger('sloth')->default(20);
            $table->unsignedTinyInteger('despair')->default(0);
            $table->string('repentance', 32)->default('none');
            $table->unsignedTinyInteger('personal_corruption')->default(0);
            $table->boolean('is_current')->nullable();
            $table->timestamps();

            $table->unique(['character_id', 'is_current'], 'spiritual_state_current_uq');
            $table->index(['world_id', 'is_current']);
        });

        $this->createIfMissing('spiritual_disposition_ledgers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->string('facet', 32);
            $table->smallInteger('delta');
            $table->string('reason', 128);
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->date('occurred_date');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['character_id', 'facet', 'occurred_date'], 'spiritual_ledger_facet_idx');
        });

        $this->createIfMissing('character_canonical_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->boolean('is_baptized')->default(false);
            $table->date('baptism_date')->nullable();
            $table->boolean('is_confirmed')->default(false);
            $table->date('confirmation_date')->nullable();
            $table->string('eucharist_standing', 32)->default('unbaptized');
            $table->string('holy_orders_grade', 32)->default('none');
            $table->unsignedBigInteger('matrimonial_bond_character_id')->nullable();
            $table->date('matrimony_date')->nullable();
            $table->date('last_anointing_date')->nullable();
            $table->string('censure', 32)->default('none');
            $table->string('censure_source_type')->nullable();
            $table->unsignedBigInteger('censure_source_id')->nullable();
            $table->boolean('grave_unconfessed')->default(false);
            $table->boolean('is_current')->nullable();
            $table->timestamps();

            $table->foreign('matrimonial_bond_character_id')->references('id')->on('characters')->nullOnDelete();
            $table->unique(['character_id', 'is_current'], 'canonical_state_current_uq');
        });

        $this->createIfMissing('sacrament_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->string('sacrament_type', 32);
            $table->foreignId('subject_character_id')->constrained('characters')->cascadeOnDelete();
            $table->unsignedBigInteger('minister_character_id')->nullable();
            $table->unsignedBigInteger('spouse_character_id')->nullable();
            $table->string('place_type', 64)->nullable();
            $table->unsignedBigInteger('place_id')->nullable();
            $table->date('occurred_date');
            $table->string('validity', 32);
            $table->json('reasons')->nullable();
            $table->string('orders_grade', 32)->nullable();
            $table->boolean('consequences_applied')->default(false);
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->foreign('minister_character_id')->references('id')->on('characters')->nullOnDelete();
            $table->foreign('spouse_character_id')->references('id')->on('characters')->nullOnDelete();
            $table->index(['subject_character_id', 'sacrament_type'], 'sacrament_subject_type_idx');
        });

        $this->createIfMissing('penances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->unsignedBigInteger('assigned_by_character_id')->nullable();
            $table->unsignedBigInteger('confession_record_id')->nullable();
            $table->string('work_type', 32);
            $table->string('status', 32);
            $table->date('assigned_date');
            $table->date('due_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->string('notes')->nullable();
            $table->timestamps();

            $table->foreign('assigned_by_character_id')->references('id')->on('characters')->nullOnDelete();
            $table->foreign('confession_record_id')->references('id')->on('sacrament_records')->nullOnDelete();
            $table->index(['character_id', 'status']);
        });

        $this->createIfMissing('scandals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->string('facet', 64);
            $table->unsignedTinyInteger('severity');
            $table->string('publicity', 32)->default('settlement');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->date('broke_date');
            $table->boolean('is_current')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['character_id', 'is_current']);
        });

        $this->createIfMissing('temptations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->string('vice', 32);
            $table->unsignedTinyInteger('intensity');
            $table->string('stage', 32)->default('pressing');
            $table->string('resolution', 32)->default('open');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->date('opened_date');
            $table->date('resolved_date')->nullable();
            $table->boolean('is_current')->nullable();
            $table->timestamps();

            $table->unique(['character_id', 'vice', 'is_current'], 'temptation_current_uq');
        });

        $this->createIfMissing('spiritual_knowledge', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('observer_character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignId('subject_character_id')->constrained('characters')->cascadeOnDelete();
            $table->string('facet', 64);
            $table->string('certainty', 32);
            $table->boolean('is_sealed')->default(false);
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->date('acquired_date');
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['observer_character_id', 'subject_character_id'], 'spiritual_knowledge_pair_idx');
        });

        $this->createIfMissing('spiritual_act_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->string('act_type', 32);
            $table->date('occurred_date');
            $table->boolean('is_public')->default(false);
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->json('deltas')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index(['character_id', 'act_type']);
        });

        if (! Schema::hasTable('corruption_states')) {
            Schema::create('corruption_states', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->string('subject_type', 32);
                $table->unsignedBigInteger('subject_id');
                $table->string('kind', 32)->nullable();
                $table->unsignedTinyInteger('intensity')->default(0);
                $table->string('source', 64)->nullable();
                $table->string('source_type')->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->json('visible_signs')->nullable();
                $table->boolean('is_current')->nullable();
                $table->date('started_date');
                $table->timestamps();
                $table->index(['world_id', 'subject_type', 'subject_id'], 'corruption_subject_idx');
            });
        } else {
            Schema::table('corruption_states', function (Blueprint $table) {
                if (! Schema::hasColumn('corruption_states', 'kind')) {
                    $table->string('kind', 32)->nullable();
                }
                if (! Schema::hasColumn('corruption_states', 'is_current')) {
                    $table->boolean('is_current')->nullable();
                }
                if (! Schema::hasColumn('corruption_states', 'source_type')) {
                    $table->string('source_type')->nullable();
                }
                if (! Schema::hasColumn('corruption_states', 'source_id')) {
                    $table->unsignedBigInteger('source_id')->nullable();
                }
                if (! Schema::hasColumn('corruption_states', 'visible_signs')) {
                    $table->json('visible_signs')->nullable();
                }
            });
        }

        $this->createIfMissing('corruption_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('corruption_state_id')->constrained('corruption_states')->cascadeOnDelete();
            $table->string('event_type', 32);
            $table->smallInteger('intensity_delta');
            $table->date('occurred_date');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        if (Schema::hasTable('demonic_influences')) {
            Schema::table('demonic_influences', function (Blueprint $table) {
                if (! Schema::hasColumn('demonic_influences', 'source_type')) {
                    $table->string('source_type')->nullable();
                }
                if (! Schema::hasColumn('demonic_influences', 'source_id')) {
                    $table->unsignedBigInteger('source_id')->nullable();
                }
                if (! Schema::hasColumn('demonic_influences', 'metadata')) {
                    $table->json('metadata')->nullable();
                }
            });
        }
    }

    private function createIfMissing(string $table, \Closure $callback): void
    {
        if (! Schema::hasTable($table)) {
            Schema::create($table, $callback);
        }
    }

    public function down(): void
    {
        Schema::table('demonic_influences', function (Blueprint $table) {
            $table->dropColumn(['source_type', 'source_id', 'metadata']);
        });
        Schema::table('corruption_states', function (Blueprint $table) {
            $table->dropColumn(['kind', 'is_current', 'source_type', 'source_id', 'visible_signs']);
        });
        Schema::dropIfExists('corruption_events');
        Schema::dropIfExists('spiritual_act_records');
        Schema::dropIfExists('spiritual_knowledge');
        Schema::dropIfExists('temptations');
        Schema::dropIfExists('scandals');
        Schema::dropIfExists('penances');
        Schema::dropIfExists('sacrament_records');
        Schema::dropIfExists('character_canonical_states');
        Schema::dropIfExists('spiritual_disposition_ledgers');
        Schema::dropIfExists('character_spiritual_states');
    }
};
