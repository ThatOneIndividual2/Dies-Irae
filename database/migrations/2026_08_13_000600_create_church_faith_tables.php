<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('faiths')) {
            return;
        }

        Schema::create('faiths', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->string('key', 128);
            $table->string('name');
            $table->string('rite', 64)->nullable();
            $table->timestamps();

            $table->unique(['world_id', 'key']);
        });

        Schema::table('characters', function (Blueprint $table) {
            $table->foreign('faith_id')->references('id')->on('faiths')->nullOnDelete();
        });

        Schema::create('church_provinces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('faith_id')->constrained('faiths')->cascadeOnDelete();
            $table->string('key', 128);
            $table->string('name');
            $table->timestamps();

            $table->unique(['world_id', 'key']);
        });

        Schema::create('sees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('church_province_id')->constrained('church_provinces')->cascadeOnDelete();
            $table->foreignId('territory_id')->nullable()->constrained('territories')->nullOnDelete();
            $table->string('key', 128);
            $table->string('name');
            $table->string('see_type', 32)->default('diocese');
            $table->timestamps();

            $table->unique(['world_id', 'key']);
        });

        Schema::create('spiritual_offices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->string('key', 128);
            $table->string('name');
            $table->string('rank', 32);
            $table->foreignId('see_id')->nullable()->constrained('sees')->nullOnDelete();
            $table->foreignId('church_province_id')->nullable()->constrained('church_provinces')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['world_id', 'key']);
            $table->index(['world_id', 'rank']);
        });

        Schema::create('spiritual_office_holderships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('spiritual_office_id')->constrained('spiritual_offices')->cascadeOnDelete();
            $table->foreignId('holder_character_id')->constrained('characters')->cascadeOnDelete();
            $table->date('acquired_date');
            $table->date('lost_date')->nullable();
            $table->boolean('is_current')->nullable();
            $table->timestamps();

            $table->unique(['spiritual_office_id', 'is_current'], 'spirit_office_current_uq');
        });

        Schema::create('clergy_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->string('status', 32);
            $table->date('started_date');
            $table->date('ended_date')->nullable();
            $table->boolean('is_current')->nullable();
            $table->timestamps();

            $table->unique(['character_id', 'is_current'], 'clergy_status_current_uq');
        });

        Schema::create('monasteries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('holding_id')->constrained('holdings')->cascadeOnDelete();
            $table->foreignId('faith_id')->constrained('faiths')->cascadeOnDelete();
            $table->string('key', 128);
            $table->string('name');
            $table->string('rule', 64)->nullable();
            $table->unsignedInteger('religious_population')->default(0);
            $table->timestamps();

            $table->unique(['world_id', 'key']);
            $table->unique('holding_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('monasteries');
        Schema::dropIfExists('clergy_statuses');
        Schema::dropIfExists('spiritual_office_holderships');
        Schema::dropIfExists('spiritual_offices');
        Schema::dropIfExists('sees');
        Schema::dropIfExists('church_provinces');
        Schema::table('characters', function (Blueprint $table) {
            $table->dropForeign(['faith_id']);
        });
        Schema::dropIfExists('faiths');
    }
};
