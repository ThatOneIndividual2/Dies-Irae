<?php

namespace App\Console\Commands;

use App\Actions\Events\InspectEventEligibility;
use App\Models\EventChainLink;
use App\Models\World;
use Illuminate\Console\Command;

class EventStatusCommand extends Command
{
    protected $signature = 'diesirae:event-status {world? : World name} {--definition=}';

    protected $description = 'Inspect event eligibility, weights, cooldowns, and chain state';

    public function handle(InspectEventEligibility $inspect): int
    {
        $name = $this->argument('world') ?: config('game.default_world_name');
        $world = World::query()->where('name', $name)->orWhere('slug', $name)->first();
        if (! $world) {
            $this->error('World not found.');

            return self::FAILURE;
        }
        $report = $inspect->execute($world, $this->option('definition') ?: null);
        $this->info($world->name.'  '.$report['date'].'  '.$report['phase']);
        $rows = array_values(array_filter($report['rows'], fn ($row) => $row['reason'] === 'eligible' || $row['weight'] > 0));
        if ($rows === []) {
            $rows = array_slice($report['rows'], 0, 12);
        }
        $this->table(
            ['Event', 'Scope', 'Weight', 'Reason', 'Cooldown'],
            array_map(fn ($row) => [
                $row['key'],
                $row['scope_type'].'#'.$row['scope_id'],
                $row['weight'],
                $row['reason'],
                $row['cooldown_until'] ?? '',
            ], $rows)
        );
        $hooks = $report['hooks']->map(fn ($h) => [
            $h->hook_key,
            $h->scope_type.'#'.$h->scope_id,
            $h->intensity,
            $h->expires_on?->toDateString(),
        ])->all();
        if ($hooks !== []) {
            $this->table(['Hook', 'Scope', 'Intensity', 'Expires'], $hooks);
        }
        $chains = EventChainLink::query()->where('world_id', $world->id)->orderByDesc('id')->limit(8)->get();
        if ($chains->isNotEmpty()) {
            $this->table(
                ['Chain', 'To', 'Due', 'Status'],
                $chains->map(fn ($c) => [$c->chain_key, $c->to_definition_key, $c->due_on?->toDateString(), $c->status])->all()
            );
        }

        return self::SUCCESS;
    }
}
