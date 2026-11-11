<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('character_death_processings')) {
            Schema::create('character_death_processings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
                $table->string('cause', 64);
                $table->date('processed_on');
                $table->foreignId('heir_character_id')->nullable()->constrained('characters')->nullOnDelete();
                $table->json('notes')->nullable();
                $table->timestamps();

                $table->unique('character_id');
            });
        }

        if (!Schema::hasTable('succession_summaries')) {
            Schema::create('succession_summaries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('deceased_character_id')->constrained('characters')->cascadeOnDelete();
                $table->foreignId('title_id')->nullable()->constrained('titles')->nullOnDelete();
                $table->foreignId('heir_character_id')->nullable()->constrained('characters')->nullOnDelete();
                $table->string('law_key', 64);
                $table->string('outcome', 32);
                $table->string('reason')->nullable();
                $table->date('processed_on');
                $table->string('fingerprint', 64);
                $table->timestamps();

                $table->unique('fingerprint');
                $table->index(['world_id', 'deceased_character_id']);
            });
        }

        if (!Schema::hasTable('hell_daily_pulses')) {
            Schema::create('hell_daily_pulses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->date('pulsed_on');
                $table->json('notes')->nullable();
                $table->timestamps();

                $table->unique(['world_id', 'pulsed_on']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('hell_daily_pulses');
        Schema::dropIfExists('succession_summaries');
        Schema::dropIfExists('character_death_processings');
    }
};
