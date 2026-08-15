<?php

namespace App\Console\Commands;

use App\Actions\Events\ForceTriggerEvent;
use App\Domain\Support\LocalInspectGuard;
use App\Models\World;
use Illuminate\Console\Command;

class EventForceCommand extends Command
{
    protected $signature = 'diesirae:event-force
        {world : World name}
        {definition : Catalog key}
        {scope_type : Scope}
        {scope_id : Scope id}';

    protected $description = 'Force-trigger a catalog event (local/testing only)';

    public function handle(ForceTriggerEvent $force): int
    {
        LocalInspectGuard::assertMutable();
        $world = World::query()
            ->where('name', $this->argument('world'))
            ->orWhere('slug', $this->argument('world'))
            ->first();
        if (! $world) {
            $this->error('World not found.');

            return self::FAILURE;
        }
        $event = $force->execute(
            $world,
            (string) $this->argument('definition'),
            (string) $this->argument('scope_type'),
            (int) $this->argument('scope_id')
        );
        $this->info($event->definition_key.' '.$event->status.' choice '.$event->chosen_option);

        return self::SUCCESS;
    }
}
