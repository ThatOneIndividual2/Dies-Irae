<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Additive saints, relics, pilgrimage, and miracle tables.
 * Extends Church saints/relics/relic_custodies; does not drop them.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('saints')) {
            Schema::table('saints', function (Blueprint $table) {
                if (! Schema::hasColumn('saints', 'origin')) {
                    $table->string('origin', 32)->default('historical');
                }
                if (! Schema::hasColumn('saints', 'feast_day')) {
                    $table->string('feast_day', 8)->nullable();
                }
                if (! Schema::hasColumn('saints', 'reputation')) {
                    $table->unsignedTinyInteger('reputation')->default(0);
                }
                if (! Schema::hasColumn('saints', 'shrine_territory_id')) {
                    $table->unsignedBigInteger('shrine_territory_id')->nullable();
                }
                if (! Schema::hasColumn('saints', 'shrine_holding_id')) {
                    $table->unsignedBigInteger('shrine_holding_id')->nullable();
                }
                if (! Schema::hasColumn('saints', 'died_date')) {
                    $table->date('died_date')->nullable();
                }
            });
        }

        if (Schema::hasTable('relics')) {
            Schema::table('relics', function (Blueprint $table) {
                if (! Schema::hasColumn('relics', 'category')) {
                    $table->string('category', 32)->default('sacred_object');
                }
                if (! Schema::hasColumn('relics', 'claimed_authenticity')) {
                    $table->string('claimed_authenticity', 32)->default('unrecognized');
                }
                if (! Schema::hasColumn('relics', 'true_nature')) {
                    $table->string('true_nature', 32)->default('doubtful');
                }
                if (! Schema::hasColumn('relics', 'claimed_provenance')) {
                    $table->string('claimed_provenance')->nullable();
                }
                if (! Schema::hasColumn('relics', 'pilgrimage_value')) {
                    $table->unsignedTinyInteger('pilgrimage_value')->default(10);
                }
                if (! Schema::hasColumn('relics', 'condition')) {
                    $table->string('condition', 32)->default('intact');
                }
                if (! Schema::hasColumn('relics', 'owner_type')) {
                    $table->string('owner_type', 32)->nullable();
                }
                if (! Schema::hasColumn('relics', 'owner_id')) {
                    $table->unsignedBigInteger('owner_id')->nullable();
                }
                if (! Schema::hasColumn('relics', 'destroyed_date')) {
                    $table->date('destroyed_date')->nullable();
                }
            });
        }

        if (Schema::hasTable('relic_custodies')) {
            Schema::table('relic_custodies', function (Blueprint $table) {
                if (! Schema::hasColumn('relic_custodies', 'custodian_type')) {
                    $table->string('custodian_type', 32)->nullable();
                }
                if (! Schema::hasColumn('relic_custodies', 'custodian_id')) {
                    $table->unsignedBigInteger('custodian_id')->nullable();
                }
                if (! Schema::hasColumn('relic_custodies', 'acquisition')) {
                    $table->string('acquisition', 32)->default('deposit');
                }
                if (! Schema::hasColumn('relic_custodies', 'owner_type')) {
                    $table->string('owner_type', 32)->nullable();
                }
                if (! Schema::hasColumn('relic_custodies', 'owner_id')) {
                    $table->unsignedBigInteger('owner_id')->nullable();
                }
            });
        }

        $this->createIfMissing('saint_evidences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('saint_id')->constrained('saints')->cascadeOnDelete();
            $table->string('evidence_type', 32);
            $table->unsignedTinyInteger('weight');
            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->date('recorded_date');
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->index(['saint_id', 'evidence_type']);
        });

        $this->createIfMissing('saint_patronages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('saint_id')->constrained('saints')->cascadeOnDelete();
            $table->string('patronage_key', 64);
            $table->string('place_type', 64)->nullable();
            $table->unsignedBigInteger('place_id')->nullable();
            $table->timestamps();
            $table->unique(['saint_id', 'patronage_key', 'place_type', 'place_id'], 'saint_patronage_uq');
        });

        $this->createIfMissing('saint_feasts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('saint_id')->constrained('saints')->cascadeOnDelete();
            $table->string('feast_day', 8);
            $table->string('rank', 32)->default('local');
            $table->unsignedBigInteger('territory_id')->nullable();
            $table->timestamps();
            $table->index(['saint_id', 'feast_day']);
        });

        $this->createIfMissing('saint_shrines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('saint_id')->constrained('saints')->cascadeOnDelete();
            $table->unsignedBigInteger('territory_id')->nullable();
            $table->unsignedBigInteger('holding_id')->nullable();
            $table->string('shrine_kind', 32)->default('chapel');
            $table->unsignedTinyInteger('pilgrimage_value')->default(10);
            $table->timestamps();
            $table->index(['saint_id', 'territory_id']);
        });

        $this->createIfMissing('saint_cults', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('saint_id')->constrained('saints')->cascadeOnDelete();
            $table->unsignedBigInteger('territory_id')->nullable();
            $table->unsignedTinyInteger('intensity')->default(1);
            $table->boolean('is_current')->nullable();
            $table->timestamps();
            $table->unique(['saint_id', 'territory_id', 'is_current'], 'saint_cult_current_uq');
        });

        $this->createIfMissing('relic_provenances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('relic_id')->constrained('relics')->cascadeOnDelete();
            $table->string('claim_text');
            $table->string('certainty', 32)->default('asserted');
            $table->boolean('is_current')->nullable();
            $table->timestamps();
            $table->index(['relic_id', 'is_current']);
        });

        $this->createIfMissing('relic_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('relic_id')->constrained('relics')->cascadeOnDelete();
            $table->string('event_type', 32);
            $table->unsignedBigInteger('actor_character_id')->nullable();
            $table->date('occurred_date');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['relic_id', 'event_type']);
        });

        $this->createIfMissing('pilgrimage_routes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->string('key', 128);
            $table->string('name');
            $table->string('destination_type', 32);
            $table->unsignedBigInteger('destination_id');
            $table->unsignedBigInteger('origin_territory_id')->nullable();
            $table->unsignedInteger('traffic_intensity')->default(0);
            $table->boolean('is_active')->default(true);
            $table->unsignedTinyInteger('prestige_yield')->default(8);
            $table->unsignedTinyInteger('income_yield')->default(12);
            $table->timestamps();
            $table->unique(['world_id', 'key']);
        });

        $this->createIfMissing('pilgrimage_stops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('pilgrimage_route_id')->constrained('pilgrimage_routes')->cascadeOnDelete();
            $table->unsignedBigInteger('territory_id');
            $table->unsignedInteger('sequence')->default(0);
            $table->unsignedInteger('lodging_pressure')->default(0);
            $table->timestamps();
            $table->index(['pilgrimage_route_id', 'sequence']);
        });

        $this->createIfMissing('pilgrimages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('character_id')->constrained('characters')->cascadeOnDelete();
            $table->foreignId('pilgrimage_route_id')->constrained('pilgrimage_routes')->cascadeOnDelete();
            $table->string('status', 32);
            $table->date('started_date');
            $table->date('completed_date')->nullable();
            $table->timestamps();
            $table->index(['character_id', 'status']);
        });

        $this->createIfMissing('pilgrimage_traffic', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('pilgrimage_route_id')->constrained('pilgrimage_routes')->cascadeOnDelete();
            $table->unsignedBigInteger('territory_id')->nullable();
            $table->unsignedInteger('pilgrims_count')->default(0);
            $table->integer('income_delta')->default(0);
            $table->integer('prestige_delta')->default(0);
            $table->unsignedInteger('cult_delta')->default(0);
            $table->date('recorded_date');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['pilgrimage_route_id', 'recorded_date']);
        });

        $this->createIfMissing('miracles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->string('category', 32);
            $table->string('status', 32)->default('claimed');
            $table->string('causation', 32)->default('unknown');
            $table->unsignedBigInteger('saint_id')->nullable();
            $table->unsignedBigInteger('relic_id')->nullable();
            $table->unsignedBigInteger('beneficiary_character_id')->nullable();
            $table->unsignedBigInteger('petitioner_character_id')->nullable();
            $table->string('place_type', 64)->nullable();
            $table->unsignedBigInteger('place_id')->nullable();
            $table->string('source_type', 64);
            $table->unsignedBigInteger('source_id')->nullable();
            $table->date('occurred_date');
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->index(['world_id', 'category', 'occurred_date']);
            $table->foreign('saint_id')->references('id')->on('saints')->nullOnDelete();
            $table->foreign('relic_id')->references('id')->on('relics')->nullOnDelete();
        });

        $this->createIfMissing('miracle_interpretations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('miracle_id')->constrained('miracles')->cascadeOnDelete();
            $table->unsignedBigInteger('interpreter_character_id')->nullable();
            $table->string('reading', 32);
            $table->boolean('is_official')->default(false);
            $table->date('recorded_date');
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->index(['miracle_id', 'is_official']);
        });

        $this->createIfMissing('miracle_witnesses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
            $table->foreignId('miracle_id')->constrained('miracles')->cascadeOnDelete();
            $table->unsignedBigInteger('character_id')->nullable();
            $table->string('certainty', 32)->default('asserted');
            $table->timestamps();
        });
    }

    private function createIfMissing(string $table, \Closure $callback): void
    {
        if (! Schema::hasTable($table)) {
            Schema::create($table, $callback);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('miracle_witnesses');
        Schema::dropIfExists('miracle_interpretations');
        Schema::dropIfExists('miracles');
        Schema::dropIfExists('pilgrimage_traffic');
        Schema::dropIfExists('pilgrimages');
        Schema::dropIfExists('pilgrimage_stops');
        Schema::dropIfExists('pilgrimage_routes');
        Schema::dropIfExists('relic_events');
        Schema::dropIfExists('relic_provenances');
        Schema::dropIfExists('saint_cults');
        Schema::dropIfExists('saint_shrines');
        Schema::dropIfExists('saint_feasts');
        Schema::dropIfExists('saint_patronages');
        Schema::dropIfExists('saint_evidences');
    }
};
