<?php

namespace App\Console\Commands;

use App\Actions\Apocalypse\RecordApocalypseSignal;
use App\Domain\Support\LocalInspectGuard;
use App\Models\World;
use Illuminate\Console\Command;

class ApocalypseSignalCommand extends Command
{
    protected $signature = 'diesirae:apocalypse-signal
        {world : World name}
        {signal : Catalog signal key}
        {magnitude=10 : 1-100}
        {--territory= : Optional territory id}
        {--idempotency= : Optional idempotency key}';

    protected $description = 'Record an apocalypse signal (local/testing only)';

    public function handle(RecordApocalypseSignal $record): int
    {
        LocalInspectGuard::assertMutable();

        $world = World::query()
            ->where('name', $this->argument('world'))
            ->orWhere('slug', $this->argument('world'))
            ->first();
        if (!$world) {
            $this->error('World not found.');
            return self::FAILURE;
        }

        $context = [];
        if ($this->option('territory')) {
            $context['territory_id'] = (int) $this->option('territory');
        }
        if ($this->option('idempotency')) {
            $context['idempotency_key'] = (string) $this->option('idempotency');
        }

        $row = $record->execute(
            $world,
            (string) $this->argument('signal'),
            (int) $this->argument('magnitude'),
            $context
        );

        $state = $world->fresh()->apocalypseState;
        $this->info('Recorded '.$row->signal_key.' x'.$row->magnitude);
        $this->line('Phase '.$state->phase_key.' pressure '.$state->pressure);

        return self::SUCCESS;
    }
}
