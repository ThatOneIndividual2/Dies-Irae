<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('realms')) {
            return;
        }

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

            $table->foreign('realm_id')->references('id')->on('realms')->nullOnDelete();
        });

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

            $table->foreign('realm_id')->references('id')->on('realms')->nullOnDelete();
            $table->foreign('primary_title_id')->references('id')->on('titles')->nullOnDelete();
            $table->foreign('contract_id')->references('id')->on('feudal_contracts')->nullOnDelete();
            $table->unique(['vassal_character_id', 'is_current'], 'vassal_rel_current_uq');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('vassal_relationships');
        Schema::dropIfExists('feudal_contracts');
        Schema::dropIfExists('realms');
    }
};
