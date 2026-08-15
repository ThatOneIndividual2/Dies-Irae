<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('apocalypse_states')) {
            Schema::table('apocalypse_states', function (Blueprint $table) {
                if (!Schema::hasColumn('apocalypse_states', 'phase_key')) {
                    $table->string('phase_key', 64)->default('ordinary')->after('world_id');
                }
                if (!Schema::hasColumn('apocalypse_states', 'phase_ordinal')) {
                    $table->unsignedTinyInteger('phase_ordinal')->default(1)->after('phase_key');
                }
                if (!Schema::hasColumn('apocalypse_states', 'phase_entered_on')) {
                    $table->date('phase_entered_on')->nullable()->after('phase_ordinal');
                }
                foreach ([
                    'global_corruption',
                    'plague_severity',
                    'demonic_manifestation',
                    'institutional_collapse',
                    'famine_pressure',
                    'despair',
                    'church_cohesion',
                    'political_fragmentation',
                    'pressure',
                ] as $column) {
                    if (!Schema::hasColumn('apocalypse_states', $column)) {
                        $table->unsignedTinyInteger($column)->default(0);
                    }
                }
                if (!Schema::hasColumn('apocalypse_states', 'meter_floors')) {
                    $table->json('meter_floors')->nullable();
                }
                if (!Schema::hasColumn('apocalypse_states', 'meter_ceilings')) {
                    $table->json('meter_ceilings')->nullable();
                }
                if (!Schema::hasColumn('apocalypse_states', 'drift_accumulators')) {
                    $table->json('drift_accumulators')->nullable();
                }
                if (!Schema::hasColumn('apocalypse_states', 'broken_assumptions')) {
                    $table->json('broken_assumptions')->nullable();
                }
                if (!Schema::hasColumn('apocalypse_states', 'tick_count')) {
                    $table->unsignedInteger('tick_count')->default(0);
                }
                if (!Schema::hasColumn('apocalypse_states', 'last_ticked_on')) {
                    $table->date('last_ticked_on')->nullable();
                }
            });
        }

        if (Schema::hasTable('scheduled_world_events')) {
            Schema::table('scheduled_world_events', function (Blueprint $table) {
                if (!Schema::hasColumn('scheduled_world_events', 'attempts')) {
                    $table->unsignedTinyInteger('attempts')->default(0)->after('status');
                }
                if (!Schema::hasColumn('scheduled_world_events', 'locked_at')) {
                    $table->timestamp('locked_at')->nullable()->after('attempts');
                }
                if (!Schema::hasColumn('scheduled_world_events', 'locked_by')) {
                    $table->string('locked_by', 64)->nullable()->after('locked_at');
                }
                if (!Schema::hasColumn('scheduled_world_events', 'failed_at')) {
                    $table->timestamp('failed_at')->nullable();
                }
                if (!Schema::hasColumn('scheduled_world_events', 'failure_message')) {
                    $table->text('failure_message')->nullable();
                }
            });
        }

        if (!Schema::hasTable('apocalypse_signals')) {
            Schema::create('apocalypse_signals', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->string('signal_key', 64);
                $table->string('polarity', 16);
                $table->unsignedSmallInteger('magnitude');
                $table->date('world_date');
                $table->string('source_type', 64)->nullable();
                $table->unsignedBigInteger('source_id')->nullable();
                $table->unsignedBigInteger('territory_id')->nullable();
                $table->json('applied_deltas')->nullable();
                $table->json('payload')->nullable();
                $table->string('idempotency_key', 191)->nullable();
                $table->timestamps();

                $table->unique(['world_id', 'idempotency_key'], 'apoc_sig_world_idem_uq');
                $table->index(['world_id', 'signal_key']);
            });
        }

        if (!Schema::hasTable('apocalypse_milestone_records')) {
            Schema::create('apocalypse_milestone_records', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->string('milestone_key', 64);
                $table->string('name');
                $table->date('reached_on');
                $table->string('phase_key', 64);
                $table->json('apply_floors')->nullable();
                $table->json('apply_ceilings')->nullable();
                $table->json('meters_at_reach')->nullable();
                $table->boolean('irreversible')->default(true);
                $table->timestamps();

                $table->unique(['world_id', 'milestone_key'], 'apoc_ms_world_key_uq');
            });
        }

        if (!Schema::hasTable('apocalypse_chronicle_entries')) {
            Schema::create('apocalypse_chronicle_entries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->date('world_date');
                $table->string('entry_type', 32);
                $table->string('subject_key', 64);
                $table->string('title');
                $table->text('body')->nullable();
                $table->json('payload')->nullable();
                $table->timestamps();

                $table->index(['world_id', 'world_date']);
            });
        }

        if (!Schema::hasTable('territory_spiritual_weather')) {
            Schema::create('territory_spiritual_weather', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->unsignedBigInteger('territory_id');
                $table->unsignedTinyInteger('local_corruption')->default(0);
                $table->unsignedTinyInteger('local_despair')->default(0);
                $table->unsignedTinyInteger('local_manifestation')->default(0);
                $table->unsignedTinyInteger('local_sanctity')->default(50);
                $table->boolean('is_sanctuary')->default(false);
                $table->date('last_changed_on')->nullable();
                $table->timestamps();

                $table->unique(['world_id', 'territory_id'], 'terr_spirit_world_terr_uq');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('territory_spiritual_weather');
        Schema::dropIfExists('apocalypse_chronicle_entries');
        Schema::dropIfExists('apocalypse_milestone_records');
        Schema::dropIfExists('apocalypse_signals');
    }
};
