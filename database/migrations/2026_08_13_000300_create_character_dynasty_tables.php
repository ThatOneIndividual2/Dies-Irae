<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('dynasties')) {
            return;
        }

        Schema::create('dynasties', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->string('key', 128);
            $table->string('name');
            $table->unsignedBigInteger('founder_character_id')->nullable();
            $table->string('motto')->nullable();
            $table->integer('prestige')->default(0);
            $table->date('founded_date')->nullable();
            $table->date('extinct_date')->nullable();
            $table->timestamps();

            $table->unique(['world_id', 'key']);
        });

        Schema::create('dynasty_houses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('dynasty_id')->constrained('dynasties')->cascadeOnDelete();
            $table->string('key', 128);
            $table->string('name');
            $table->unsignedBigInteger('parent_house_id')->nullable();
            $table->unsignedBigInteger('head_character_id')->nullable();
            $table->integer('prestige')->default(0);
            $table->timestamps();

            $table->unique(['world_id', 'key']);
            $table->foreign('parent_house_id')->references('id')->on('dynasty_houses')->nullOnDelete();
        });

        Schema::create('characters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->string('key', 128);
            $table->foreignId('dynasty_id')->nullable()->constrained('dynasties')->nullOnDelete();
            $table->foreignId('house_id')->nullable()->constrained('dynasty_houses')->nullOnDelete();
            $table->unsignedBigInteger('father_id')->nullable();
            $table->unsignedBigInteger('mother_id')->nullable();
            $table->unsignedBigInteger('spouse_id')->nullable();
            $table->string('first_name');
            $table->string('epithet')->nullable();
            $table->string('sex', 16);
            $table->date('birth_date');
            $table->date('death_date')->nullable();
            $table->boolean('is_alive')->default(true);
            $table->string('culture')->nullable();
            $table->unsignedBigInteger('faith_id')->nullable();
            $table->string('legitimacy_status', 32)->default('legitimate');
            $table->unsignedTinyInteger('health')->default(100);
            $table->unsignedTinyInteger('martial')->default(5);
            $table->unsignedTinyInteger('diplomacy')->default(5);
            $table->unsignedTinyInteger('stewardship')->default(5);
            $table->unsignedTinyInteger('intrigue')->default(5);
            $table->unsignedTinyInteger('learning')->default(5);
            $table->integer('prestige')->default(0);
            $table->timestamps();

            $table->unique(['world_id', 'key']);
            $table->foreign('father_id')->references('id')->on('characters')->nullOnDelete();
            $table->foreign('mother_id')->references('id')->on('characters')->nullOnDelete();
            $table->foreign('spouse_id')->references('id')->on('characters')->nullOnDelete();
            $table->index(['world_id', 'is_alive']);
        });

        Schema::table('dynasties', function (Blueprint $table) {
            $table->foreign('founder_character_id')->references('id')->on('characters')->nullOnDelete();
        });

        Schema::table('dynasty_houses', function (Blueprint $table) {
            $table->foreign('head_character_id')->references('id')->on('characters')->nullOnDelete();
        });

        Schema::table('territories', function (Blueprint $table) {
            if (Schema::hasColumn('territories', 'owner_character_id')) {
                $table->foreign('owner_character_id')->references('id')->on('characters')->nullOnDelete();
            }
            if (Schema::hasColumn('territories', 'controller_character_id')) {
                $table->foreign('controller_character_id')->references('id')->on('characters')->nullOnDelete();
            }
        });

        Schema::table('holdings', function (Blueprint $table) {
            if (Schema::hasTable('holdings') && Schema::hasColumn('holdings', 'owner_character_id')) {
                $table->foreign('owner_character_id')->references('id')->on('characters')->nullOnDelete();
            }
            if (Schema::hasTable('holdings') && Schema::hasColumn('holdings', 'controller_character_id')) {
                $table->foreign('controller_character_id')->references('id')->on('characters')->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('holdings', function (Blueprint $table) {
            $table->dropForeign(['owner_character_id']);
            $table->dropForeign(['controller_character_id']);
        });
        Schema::table('territories', function (Blueprint $table) {
            $table->dropForeign(['owner_character_id']);
            $table->dropForeign(['controller_character_id']);
        });
        Schema::table('dynasty_houses', function (Blueprint $table) {
            $table->dropForeign(['head_character_id']);
        });
        Schema::table('dynasties', function (Blueprint $table) {
            $table->dropForeign(['founder_character_id']);
        });

        Schema::dropIfExists('characters');
        Schema::dropIfExists('dynasty_houses');
        Schema::dropIfExists('dynasties');
    }
};
