<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('territories') && ! Schema::hasColumn('territories', 'agricultural_capacity')) {
            Schema::table('territories', function (Blueprint $table) {
                $table->unsignedTinyInteger('agricultural_capacity')->default(0);
            });
        }

        if (! Schema::hasTable('political_relations')) {
            Schema::create('political_relations', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->unsignedBigInteger('from_realm_id');
                $table->unsignedBigInteger('to_realm_id');
                $table->string('type', 32);
                $table->string('name');
                $table->date('started_date');
                $table->text('note')->nullable();
                $table->timestamps();
                $table->index(['world_id', 'type']);
            });
        }

        if (! Schema::hasTable('trade_corridors')) {
            Schema::create('trade_corridors', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->string('key', 128);
                $table->string('name');
                $table->string('good', 64)->nullable();
                $table->json('stop_territory_ids')->nullable();
                $table->timestamps();
                $table->unique(['world_id', 'key']);
            });
        }

        if (! Schema::hasTable('pilgrimage_sites')) {
            Schema::create('pilgrimage_sites', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->string('key', 128);
                $table->string('name');
                $table->unsignedBigInteger('territory_id')->nullable();
                $table->string('dedication')->nullable();
                $table->unsignedTinyInteger('importance')->default(50);
                $table->timestamps();
                $table->unique(['world_id', 'key']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('pilgrimage_sites');
        Schema::dropIfExists('trade_corridors');
        Schema::dropIfExists('political_relations');
        if (Schema::hasTable('territories') && Schema::hasColumn('territories', 'agricultural_capacity')) {
            Schema::table('territories', function (Blueprint $table) {
                $table->dropColumn('agricultural_capacity');
            });
        }
    }
};
