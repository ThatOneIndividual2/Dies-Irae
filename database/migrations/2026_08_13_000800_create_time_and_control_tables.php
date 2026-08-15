<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('scheduled_world_events')) {
            Schema::create('scheduled_world_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->date('process_at');
            $table->string('event_type', 64);
            $table->string('status', 32)->default('pending');
            $table->json('payload')->nullable();
            $table->string('idempotency_key', 191)->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['world_id', 'idempotency_key'], 'sched_evt_world_idem_uq');
            $table->index(['world_id', 'status', 'process_at']);
            });
        }

        if (!Schema::hasTable('character_control_histories')) {
            Schema::create('character_control_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->timestamp('started_at');
            $table->timestamp('ended_at')->nullable();
            $table->string('reason')->nullable();
            $table->boolean('is_current')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'is_current']);
            $table->unique(['character_id', 'is_current'], 'ctrl_hist_character_current_uq');
            });
        }

        if (!Schema::hasColumn('users', 'controlled_character_id')) {
            Schema::table('users', function (Blueprint $table) {
            $table->foreignId('controlled_character_id')
                ->nullable()
                ->after('remember_token')
                ->constrained('characters')
                ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('controlled_character_id');
        });
        Schema::dropIfExists('character_control_histories');
        Schema::dropIfExists('scheduled_world_events');
    }
};
