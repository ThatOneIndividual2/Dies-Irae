<?php

namespace App\Console\Commands;

use App\Actions\Apocalypse\EnsureApocalypseState;
use App\Models\ApocalypseChronicleEntry;
use App\Models\ApocalypseMilestoneRecord;
use App\Models\ApocalypseSignal;
use App\Models\World;
use Illuminate\Console\Command;

class ApocalypseStatusCommand extends Command
{
    protected $signature = 'diesirae:apocalypse-status {world? : World name}';

    protected $description = 'Show apocalypse phase, meters, floors, and recent chronicle';

    public function handle(EnsureApocalypseState $ensure): int
    {
        $world = $this->resolveWorld();
        if (!$world) {
            $this->error('World not found.');
            return self::FAILURE;
        }

        $state = $ensure->execute($world);
        $this->info($world->name.'  '.$world->current_date->toDateString());
        $this->line('Phase: '.$state->phase_key.' (ordinal '.$state->phase_ordinal.')');
        $this->line('Pressure: '.$state->pressure);
        $this->line('Ticks: '.$state->tick_count.'  last: '.($state->last_ticked_on?->toDateString() ?? 'never'));
        $this->table(
            ['Meter', 'Value', 'Floor'],
            collect($state->meters()->all())->map(function ($value, $key) use ($state) {
                return [$key, $value, $state->meter_floors[$key] ?? 0];
            })->values()->all()
        );

        $milestones = ApocalypseMilestoneRecord::query()
            ->where('world_id', $world->id)
            ->orderBy('id')
            ->get(['milestone_key', 'reached_on']);
        $this->line('Milestones: '.($milestones->pluck('milestone_key')->implode(', ') ?: '(none)'));

        $signals = ApocalypseSignal::query()
            ->where('world_id', $world->id)
            ->selectRaw('signal_key, COUNT(*) as n, SUM(magnitude) as mag')
            ->groupBy('signal_key')
            ->get();
        if ($signals->isNotEmpty()) {
            $this->table(['Signal', 'Count', 'Magnitude sum'], $signals->map(fn ($r) => [
                $r->signal_key, $r->n, $r->mag,
            ])->all());
        }

        $chronicle = ApocalypseChronicleEntry::query()
            ->where('world_id', $world->id)
            ->orderByDesc('id')
            ->limit(8)
            ->get();
        foreach ($chronicle as $entry) {
            $this->line($entry->world_date->toDateString().' ['.$entry->entry_type.'] '.$entry->title);
        }

        return self::SUCCESS;
    }

    private function resolveWorld(): ?World
    {
        $name = $this->argument('world') ?: config('game.default_world_name');

        return World::query()->where('name', $name)->orWhere('slug', $name)->first();
    }
}
