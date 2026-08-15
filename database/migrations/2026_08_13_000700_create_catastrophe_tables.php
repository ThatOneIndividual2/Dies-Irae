<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('plague_waves')) {
            Schema::create('plague_waves', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->string('key', 128);
                $table->string('name');
                $table->date('started_date');
                $table->date('ended_date')->nullable();
                $table->string('status', 32)->default('active');
                $table->timestamps();

                $table->unique(['world_id', 'key']);
            });
        } elseif (! Schema::hasColumn('plague_waves', 'key')) {
            Schema::table('plague_waves', function (Blueprint $table) {
                $table->string('key', 128)->nullable();
                $table->date('started_date')->nullable();
                $table->string('status', 32)->nullable();
            });
        }

        if (! Schema::hasTable('territory_plague_states')) {
            Schema::create('territory_plague_states', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('plague_wave_id')->constrained('plague_waves')->cascadeOnDelete();
                $table->foreignId('territory_id')->constrained('territories')->cascadeOnDelete();
                $table->unsignedTinyInteger('intensity')->default(1);
                $table->date('arrived_date');
                $table->date('peaked_date')->nullable();
                $table->timestamps();

                $table->unique(['plague_wave_id', 'territory_id']);
            });
        }

        if (! Schema::hasTable('corruption_states')) {
            Schema::create('corruption_states', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->string('subject_type', 32);
                $table->unsignedBigInteger('subject_id');
                $table->unsignedTinyInteger('intensity')->default(1);
                $table->string('source', 64)->nullable();
                $table->date('started_date');
                $table->timestamps();

                $table->index(['world_id', 'subject_type', 'subject_id'], 'corruption_subject_idx');
            });
        }

        if (! Schema::hasTable('demonic_factions')) {
            Schema::create('demonic_factions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->string('key', 128);
                $table->string('name');
                $table->string('status', 32)->default('dormant');
                $table->text('agenda')->nullable();
                $table->timestamps();

                $table->unique(['world_id', 'key']);
            });
        }

        if (! Schema::hasTable('demonic_threats')) {
            Schema::create('demonic_threats', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('demonic_faction_id')->constrained('demonic_factions')->cascadeOnDelete();
                $table->foreignId('territory_id')->nullable()->constrained('territories')->nullOnDelete();
                $table->string('key', 128);
                $table->string('name');
                $table->string('status', 32)->default('dormant');
                $table->unsignedTinyInteger('intensity')->default(0);
                $table->timestamps();

                $table->unique(['world_id', 'key']);
            });
        }

        if (! Schema::hasTable('apocalypse_states')) {
            Schema::create('apocalypse_states', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->string('stage', 64)->default('ordinary_order');
                $table->json('signs')->nullable();
                $table->date('stage_entered_date');
                $table->timestamps();

                $table->unique('world_id');
            });
        } else {
            Schema::table('apocalypse_states', function (Blueprint $table) {
                if (! Schema::hasColumn('apocalypse_states', 'stage')) {
                    $table->string('stage', 64)->nullable();
                }
                if (! Schema::hasColumn('apocalypse_states', 'signs')) {
                    $table->json('signs')->nullable();
                }
                if (! Schema::hasColumn('apocalypse_states', 'stage_entered_date')) {
                    $table->date('stage_entered_date')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('apocalypse_states');
        Schema::dropIfExists('demonic_threats');
        Schema::dropIfExists('demonic_factions');
        Schema::dropIfExists('corruption_states');
        Schema::dropIfExists('territory_plague_states');
    }
};
