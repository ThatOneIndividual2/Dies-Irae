<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Named infernal identities, knowledge, grudges, and history.
 * Additive. Incursions do not require these rows.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('named_demons')) {
            Schema::table('named_demons', function (Blueprint $table) {
                if (!Schema::hasColumn('named_demons', 'true_name')) {
                    $table->string('true_name')->nullable();
                }
                if (!Schema::hasColumn('named_demons', 'public_alias')) {
                    $table->string('public_alias')->nullable();
                }
                if (!Schema::hasColumn('named_demons', 'strategy')) {
                    $table->string('strategy', 32)->nullable();
                }
                if (!Schema::hasColumn('named_demons', 'hierarchy')) {
                    $table->string('hierarchy', 64)->nullable();
                }
                if (!Schema::hasColumn('named_demons', 'epithets')) {
                    $table->json('epithets')->nullable();
                }
                if (!Schema::hasColumn('named_demons', 'titles')) {
                    $table->json('titles')->nullable();
                }
                if (!Schema::hasColumn('named_demons', 'themes')) {
                    $table->json('themes')->nullable();
                }
                if (!Schema::hasColumn('named_demons', 'channels')) {
                    $table->json('channels')->nullable();
                }
                if (!Schema::hasColumn('named_demons', 'hidden_true_state')) {
                    $table->json('hidden_true_state')->nullable();
                }
                if (!Schema::hasColumn('named_demons', 'vulnerabilities')) {
                    $table->json('vulnerabilities')->nullable();
                }
                if (!Schema::hasColumn('named_demons', 'known_manifestations')) {
                    $table->json('known_manifestations')->nullable();
                }
                if (!Schema::hasColumn('named_demons', 'physical_manifest_min_apocalypse')) {
                    $table->unsignedTinyInteger('physical_manifest_min_apocalypse')->default(40);
                }
                if (!Schema::hasColumn('named_demons', 'physical_manifest_forbidden_phases')) {
                    $table->json('physical_manifest_forbidden_phases')->nullable();
                }
            });
        }

        if (!Schema::hasTable('named_demon_objectives')) {
            Schema::create('named_demon_objectives', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('named_demon_id')->constrained('named_demons')->cascadeOnDelete();
                $table->string('key', 64);
                $table->string('aim');
                $table->string('status', 32)->default('open');
                $table->timestamps();
                $table->unique(['named_demon_id', 'key']);
            });
        }

        if (!Schema::hasTable('named_demon_servants')) {
            Schema::create('named_demon_servants', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('named_demon_id')->constrained('named_demons')->cascadeOnDelete();
                $table->string('instance_key', 64);
                $table->string('kind', 32);
                $table->string('subject_id', 64);
                $table->boolean('imprisoned')->default(false);
                $table->timestamps();
            });
        }

        if (!Schema::hasTable('named_demon_rivalries')) {
            Schema::create('named_demon_rivalries', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('named_demon_id')->constrained('named_demons')->cascadeOnDelete();
                $table->string('rival_catalog_key', 64);
                $table->string('reason')->nullable();
                $table->timestamps();
                $table->unique(['named_demon_id', 'rival_catalog_key'], 'named_demon_rival_uq');
            });
        }

        if (!Schema::hasTable('named_demon_territory_influence')) {
            Schema::create('named_demon_territory_influence', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('named_demon_id')->constrained('named_demons')->cascadeOnDelete();
                $table->unsignedBigInteger('territory_id');
                $table->unsignedTinyInteger('intensity')->default(0);
                $table->timestamps();
                $table->unique(['named_demon_id', 'territory_id'], 'named_demon_terr_inf_uq');
            });
        }

        if (!Schema::hasTable('named_demon_acts')) {
            Schema::create('named_demon_acts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('named_demon_id')->constrained('named_demons')->cascadeOnDelete();
                $table->date('acted_on');
                $table->string('channel', 64);
                $table->string('summary');
                $table->json('payload')->nullable();
                $table->timestamps();
                $table->index(['named_demon_id', 'acted_on']);
            });
        }

        if (!Schema::hasTable('named_demon_grudges')) {
            Schema::create('named_demon_grudges', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('named_demon_id')->constrained('named_demons')->cascadeOnDelete();
                $table->string('subject_type', 32);
                $table->string('subject_id', 64);
                $table->string('reason');
                $table->unsignedTinyInteger('intensity')->default(1);
                $table->timestamps();
                $table->unique(['named_demon_id', 'subject_type', 'subject_id'], 'named_demon_grudge_uq');
            });
        }

        if (!Schema::hasTable('named_demon_knowledge')) {
            Schema::create('named_demon_knowledge', function (Blueprint $table) {
                $table->id();
                $table->foreignId('world_id')->constrained('worlds')->cascadeOnDelete();
                $table->foreignId('named_demon_id')->constrained('named_demons')->cascadeOnDelete();
                $table->string('observer_key', 64);
                $table->string('facet', 32);
                $table->string('source', 32);
                $table->date('learned_on');
                $table->timestamps();
                $table->unique(['named_demon_id', 'observer_key', 'facet'], 'named_demon_know_uq');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('named_demon_knowledge');
        Schema::dropIfExists('named_demon_grudges');
        Schema::dropIfExists('named_demon_acts');
        Schema::dropIfExists('named_demon_territory_influence');
        Schema::dropIfExists('named_demon_rivalries');
        Schema::dropIfExists('named_demon_servants');
        Schema::dropIfExists('named_demon_objectives');
    }
};
