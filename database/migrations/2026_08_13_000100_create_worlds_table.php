<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('worlds')) {
            Schema::create('worlds', function (Blueprint $table) {
                $table->id();
                $table->string('slug', 64)->unique();
                $table->string('name');
                $table->string('status', 32)->default('running');
                $table->date('start_date');
                $table->date('game_date');
                $table->date('current_date')->nullable();
                $table->unsignedTinyInteger('game_speed')->default(2);
                $table->string('simulation_seed', 64)->nullable();
                $table->unsignedInteger('political_map_version')->default(0);
                $table->timestamp('last_processed_at')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('worlds', function (Blueprint $table) {
            if (! Schema::hasColumn('worlds', 'game_date')) {
                $table->date('game_date')->nullable();
            }
            if (! Schema::hasColumn('worlds', 'political_map_version')) {
                $table->unsignedInteger('political_map_version')->default(0);
            }
            if (! Schema::hasColumn('worlds', 'slug')) {
                $table->string('slug', 64)->nullable();
            }
        });
    }

    public function down(): void
    {
        // Shared with the world kernel migration. Fresh drops handle teardown.
    }
};
