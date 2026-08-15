<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Dynamic narrative event engine. Additive. Does not reset worlds.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('game_events')) {
            Schema::table('game_events', function (Blueprint $table) {
                if (! Schema::hasColumn('game_events', 'engine')) {
                    $table->string('engine', 32)->default('campaign_beat')->after('event_key');
                }
                if (! Schema::hasColumn('game_events', 'definition_key')) {
                    $table->string('definition_key', 128)->nullable()->after('engine');
                }
                if (! Schema::hasColumn('game_events', 'category')) {
                    $table->string('category', 32)->nullable()->after('definition_key');
                }
                if (! Schema::hasColumn('game_events', 'scope_type')) {
                    $table->string('scope_type', 32)->nullable()->after('category');
                }
                if (! Schema::hasColumn('game_events', 'scope_id')) {
                    $table->unsignedBigInteger('scope_id')->nullable()->after('scope_type');
                }
                if (! Schema::hasColumn('game_events', 'actor_character_id')) {
                    $table->unsignedBigInteger('actor_character_id')->nullable()->after('scope_id');
                }
                if (! Schema::hasColumn('game_events', 'visibility')) {
                    $table->string('visibility', 32)->default('player')->after('actor_character_id');
                }
                if (! Schema::hasColumn('game_events', 'exclusivity_group')) {
                    $table->string('exclusivity_group', 64)->nullable()->after('visibility');
                }
                if (! Schema::hasColumn('game_events', 'occurrence_key')) {
                    $table->string('occurrence_key', 191)->nullable()->after('exclusivity_group');
                }
                if (! Schema::hasColumn('game_events', 'chain_key')) {
                    $table->string('chain_key', 64)->nullable()->after('occurrence_key');
                }
                if (! Schema::hasColumn('game_events', 'parent_event_id')) {
                    $table->unsignedBigInteger('parent_event_id')->nullable()->after('chain_key');
                }
                if (! Schema::hasColumn('game_events', 'weight_at_fire')) {
                    $table->unsignedInteger('weight_at_fire')->nullable()->after('parent_event_id');
                }
            });

            $missingKeys = DB::table('game_events')->whereNull('occurrence_key')->get(['id', 'world_id', 'event_key']);
            foreach ($missingKeys as $row) {
                DB::table('game_events')->where('id', $row->id)->update([
                    'occurrence_key' => $row->world_id.':'.$row->event_key,
                    'definition_key' => $row->event_key,
                ]);
            }

            $occ = DB::select("SHOW INDEX FROM game_events WHERE Key_name = 'game_events_world_occ_uq'");
            if ($occ === []) {
                Schema::table('game_events', function (Blueprint $table) {
                    $table->unique(['world_id', 'occurrence_key'], 'game_events_world_occ_uq');
                    $table->index(['world_id', 'status', 'engine'], 'game_events_world_status_engine_idx');
                    $table->index(['world_id', 'definition_key'], 'game_events_world_def_idx');
                });
            }
        }

        if (! Schema::hasTable('event_hooks')) {
            Schema::create('event_hooks', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->string('hook_key', 64);
                $table->string('scope_type', 32)->default('world');
                $table->unsignedBigInteger('scope_id')->nullable();
                $table->unsignedInteger('intensity')->default(1);
                $table->json('payload')->nullable();
                $table->unsignedBigInteger('source_event_id')->nullable();
                $table->string('source_choice', 64)->nullable();
                $table->date('set_on');
                $table->date('expires_on')->nullable();
                $table->timestamps();

                $table->unique(['world_id', 'hook_key', 'scope_type', 'scope_id'], 'event_hooks_world_key_scope_uq');
                $table->index(['world_id', 'hook_key']);
            });
        }

        if (! Schema::hasTable('event_cooldowns')) {
            Schema::create('event_cooldowns', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->string('definition_key', 128);
                $table->string('scope_type', 32)->default('world');
                $table->unsignedBigInteger('scope_id')->nullable();
                $table->date('available_on');
                $table->date('last_fired_on');
                $table->timestamps();

                $table->unique(['world_id', 'definition_key', 'scope_type', 'scope_id'], 'event_cd_world_def_scope_uq');
            });
        }

        if (! Schema::hasTable('event_chain_links')) {
            Schema::create('event_chain_links', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->string('chain_key', 64);
                $table->unsignedBigInteger('from_event_id')->nullable();
                $table->string('from_choice', 64)->nullable();
                $table->string('to_definition_key', 128)->nullable();
                $table->string('scope_type', 32)->nullable();
                $table->unsignedBigInteger('scope_id')->nullable();
                $table->string('status', 32)->default('pending');
                $table->date('due_on')->nullable();
                $table->json('ops')->nullable();
                $table->json('when_clause')->nullable();
                $table->json('payload')->nullable();
                $table->text('skip_reason')->nullable();
                $table->timestamp('resolved_at')->nullable();
                $table->timestamps();

                $table->index(['world_id', 'status', 'due_on'], 'event_chain_due_idx');
            });
        }

        if (! Schema::hasTable('event_hidden_logs')) {
            Schema::create('event_hidden_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->unsignedBigInteger('game_event_id')->nullable();
                $table->string('definition_key', 128)->nullable();
                $table->string('choice_key', 64)->nullable();
                $table->string('op', 64);
                $table->json('result')->nullable();
                $table->date('applied_on');
                $table->timestamps();

                $table->index(['world_id', 'game_event_id'], 'event_hidden_event_idx');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('event_hidden_logs');
        Schema::dropIfExists('event_chain_links');
        Schema::dropIfExists('event_cooldowns');
        Schema::dropIfExists('event_hooks');
    }
};
