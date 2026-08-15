<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('succession_laws')) {
        Schema::create('succession_laws', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->string('description')->nullable();
            $table->timestamps();
        });
        }

        if (! Schema::hasTable('titles')) {
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
            $table->index(['world_id', 'rank']);
        });
        }

        if (! Schema::hasTable('title_ownerships')) {
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
            $table->index(['holder_character_id', 'is_current']);
        });
        }

        if (! Schema::hasTable('title_claims')) {
        Schema::create('title_claims', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('title_id')->constrained('titles')->cascadeOnDelete();
            $table->foreignId('claimant_character_id')->constrained('characters')->cascadeOnDelete();
            $table->string('claim_type', 32);
            $table->unsignedTinyInteger('strength')->default(50);
            $table->boolean('is_active')->default(true);
            $table->date('created_date');
            $table->timestamps();
        });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('title_claims');
        Schema::dropIfExists('title_ownerships');
        Schema::dropIfExists('titles');
        Schema::dropIfExists('succession_laws');
    }
};
