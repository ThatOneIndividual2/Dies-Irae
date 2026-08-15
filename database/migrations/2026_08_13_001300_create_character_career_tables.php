<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('characters', function (Blueprint $table) {
            if (!Schema::hasColumn('characters', 'theology')) {
                $table->unsignedTinyInteger('theology')->default(5);
            }
            if (!Schema::hasColumn('characters', 'medicine')) {
                $table->unsignedTinyInteger('medicine')->default(5);
            }
            if (!Schema::hasColumn('characters', 'leadership')) {
                $table->unsignedTinyInteger('leadership')->default(5);
            }
            if (!Schema::hasColumn('characters', 'piety_reputation')) {
                $table->unsignedTinyInteger('piety_reputation')->default(5);
            }
        });

        Schema::create('character_careers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->string('career_key', 64);
            $table->date('started_date');
            $table->date('ended_date')->nullable();
            $table->boolean('is_current')->nullable();
            $table->timestamps();

            $table->unique(['character_id', 'is_current'], 'character_career_current_uq');
            $table->index(['world_id', 'career_key']);
        });

        Schema::create('character_life_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->string('state_key', 64);
            $table->date('started_date');
            $table->date('ended_date')->nullable();
            $table->boolean('is_current')->nullable();
            $table->timestamps();

            $table->index(['character_id', 'state_key', 'is_current'], 'character_life_state_idx');
        });

        Schema::create('character_educations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->string('source', 64);
            $table->date('started_date');
            $table->date('ended_date')->nullable();
            $table->boolean('is_complete')->default(false);
            $table->timestamps();

            $table->index(['world_id', 'character_id']);
        });

        Schema::create('character_career_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->string('event_key', 64);
            $table->json('payload')->nullable();
            $table->date('occurred_date');
            $table->timestamps();

            $table->index(['world_id', 'character_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('character_career_events');
        Schema::dropIfExists('character_educations');
        Schema::dropIfExists('character_life_states');
        Schema::dropIfExists('character_careers');
        Schema::table('characters', function (Blueprint $table) {
            foreach (['theology', 'medicine', 'leadership', 'piety_reputation'] as $column) {
                if (Schema::hasColumn('characters', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
