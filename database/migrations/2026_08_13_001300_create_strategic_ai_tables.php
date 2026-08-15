<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('character_personalities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->unsignedTinyInteger('ambitious')->default(40);
            $table->unsignedTinyInteger('pious')->default(40);
            $table->unsignedTinyInteger('cautious')->default(40);
            $table->unsignedTinyInteger('vengeful')->default(20);
            $table->unsignedTinyInteger('greedy')->default(30);
            $table->unsignedTinyInteger('loyal')->default(50);
            $table->unsignedTinyInteger('zealous')->default(30);
            $table->unsignedTinyInteger('compassionate')->default(40);
            $table->timestamps();

            $table->unique(['world_id', 'character_id']);
        });

        Schema::create('ai_memories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->string('actor_grain', 32);
            $table->string('actor_key', 64);
            $table->string('memory_key', 64);
            $table->string('kind', 32);
            $table->unsignedTinyInteger('salience')->default(50);
            $table->string('related_action', 64)->nullable();
            $table->date('recorded_on');
            $table->timestamps();

            $table->index(['world_id', 'actor_grain', 'actor_key']);
        });

        Schema::create('ai_cooldowns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->string('actor_grain', 32);
            $table->string('actor_key', 64);
            $table->string('action_key', 64);
            $table->date('available_on');
            $table->timestamps();

            $table->unique(['world_id', 'actor_grain', 'actor_key', 'action_key'], 'ai_cooldowns_actor_action_uq');
        });

        Schema::create('ai_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->string('actor_grain', 32);
            $table->string('actor_key', 64);
            $table->string('target_key', 64);
            $table->string('kind', 32)->default('acquaintance');
            $table->smallInteger('standing')->default(0);
            $table->timestamps();

            $table->unique(['world_id', 'actor_grain', 'actor_key', 'target_key'], 'ai_relationships_pair_uq');
        });

        Schema::create('ai_decision_traces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->string('actor_grain', 32);
            $table->string('actor_key', 64);
            $table->string('actor_type', 32);
            $table->string('chosen_action', 64);
            $table->decimal('score', 8, 4);
            $table->json('trace');
            $table->date('decided_on');
            $table->timestamps();

            $table->index(['world_id', 'actor_key', 'decided_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_decision_traces');
        Schema::dropIfExists('ai_relationships');
        Schema::dropIfExists('ai_cooldowns');
        Schema::dropIfExists('ai_memories');
        Schema::dropIfExists('character_personalities');
    }
};
