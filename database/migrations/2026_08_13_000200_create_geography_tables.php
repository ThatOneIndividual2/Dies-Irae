<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('regions')) {
            Schema::create('regions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->string('key', 128);
                $table->string('name');
                $table->string('map_key', 128)->nullable();
                $table->unsignedBigInteger('parent_region_id')->nullable();
                $table->timestamps();

                $table->unique(['world_id', 'key']);
                $table->foreign('parent_region_id')->references('id')->on('regions')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('territories')) {
            Schema::create('territories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
                $table->unsignedBigInteger('parent_territory_id')->nullable();
                $table->string('key', 128)->nullable();
                $table->string('name');
                $table->string('territory_type', 32)->default('county');
                $table->string('terrain_type', 32)->nullable();
                $table->unsignedInteger('population')->default(0);
                $table->unsignedInteger('development')->default(0);
                $table->unsignedTinyInteger('control')->default(100);
                $table->unsignedBigInteger('owner_character_id')->nullable();
                $table->unsignedBigInteger('controller_character_id')->nullable();
                $table->string('supernatural_state', 32)->default('ordinary');
                $table->string('ruin_state', 32)->default('intact');
                $table->timestamps();

                $table->unique(['world_id', 'key']);
                $table->foreign('parent_territory_id')->references('id')->on('territories')->nullOnDelete();
            });
        } else {
            Schema::table('territories', function (Blueprint $table) {
                if (!Schema::hasColumn('territories', 'region_id')) {
                    $table->unsignedBigInteger('region_id')->nullable();
                }
                if (!Schema::hasColumn('territories', 'key')) {
                    $table->string('key', 128)->nullable();
                }
                if (!Schema::hasColumn('territories', 'population')) {
                    $table->unsignedInteger('population')->default(0);
                }
                if (!Schema::hasColumn('territories', 'territory_type')) {
                    $table->string('territory_type', 32)->default('county');
                }
                if (!Schema::hasColumn('territories', 'terrain_type')) {
                    $table->string('terrain_type', 32)->nullable();
                }
                if (!Schema::hasColumn('territories', 'development')) {
                    $table->unsignedInteger('development')->default(0);
                }
                if (!Schema::hasColumn('territories', 'control')) {
                    $table->unsignedTinyInteger('control')->default(100);
                }
                if (!Schema::hasColumn('territories', 'supernatural_state')) {
                    $table->string('supernatural_state', 32)->default('ordinary');
                }
                if (!Schema::hasColumn('territories', 'ruin_state')) {
                    $table->string('ruin_state', 32)->default('intact');
                }
                if (!Schema::hasColumn('territories', 'owner_character_id')) {
                    $table->unsignedBigInteger('owner_character_id')->nullable();
                }
                if (!Schema::hasColumn('territories', 'controller_character_id')) {
                    $table->unsignedBigInteger('controller_character_id')->nullable();
                }
            });
        }

        if (!Schema::hasTable('holdings')) {
            Schema::create('holdings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('territory_id')->constrained('territories')->cascadeOnDelete();
                $table->string('key', 128);
                $table->string('name');
                $table->string('holding_type', 32);
                $table->unsignedInteger('development')->default(0);
                $table->unsignedInteger('fortification')->default(0);
                $table->unsignedInteger('base_tax')->default(0);
                $table->unsignedInteger('base_levy')->default(0);
                $table->unsignedBigInteger('owner_character_id')->nullable();
                $table->unsignedBigInteger('controller_character_id')->nullable();
                $table->timestamps();

                $table->unique(['world_id', 'key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('holdings');
        Schema::dropIfExists('territories');
        Schema::dropIfExists('regions');
    }
};
