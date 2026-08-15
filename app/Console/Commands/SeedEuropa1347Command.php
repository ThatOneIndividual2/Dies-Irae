<?php

namespace App\Console\Commands;

use App\Actions\Campaign\SeedEuropa1347;
use App\Domain\Campaign\PlayerArchetype;
use App\Models\User;
use App\Models\World;
use Illuminate\Console\Command;

class SeedEuropa1347Command extends Command
{
    protected $signature = 'diesirae:seed-1347 {archetype=king} {--email=} {--password=} {--force} {--seed=}';
    protected $description = 'Seed the Europe 1347 campaign world (does not touch other worlds unless --force on europa-1347)';

    public function handle(SeedEuropa1347 $seed): int
    {
        $archetype = (string) $this->argument('archetype');
        PlayerArchetype::assertValid($archetype);
        $slug = config('campaign.1347.world_slug');
        $existing = World::query()->where('slug', $slug)->first();
        if ($existing && !$this->option('force')) {
            $this->warn('europa-1347 already exists. Pass --force to rebuild that world only.');
            return self::SUCCESS;
        }
        if ($existing && $this->option('force')) {
            User::query()->where('world_id', $existing->id)->delete();
            $existing->delete();
        }

        $ctx = $seed->execute(
            $archetype,
            $this->option('email') ?: null,
            $this->option('password') ?: null,
            $this->option('seed') !== null ? (int) $this->option('seed') : null
        );

        $this->info('Seeded '.$ctx->world->name.' as '.$archetype.'.');
        $this->info('Login: '.$ctx->user->email.' / password');
        $this->info('Ruler: '.$ctx->player->displayName());

        return self::SUCCESS;
    }
}
