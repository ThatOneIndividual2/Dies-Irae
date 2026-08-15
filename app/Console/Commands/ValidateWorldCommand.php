<?php

namespace App\Console\Commands;

use App\Domain\World\HistoricalPack;
use App\Models\World;
use App\Validation\WorldIntegrityValidator;
use Illuminate\Console\Command;

class ValidateWorldCommand extends Command
{
    protected $signature = 'diesirae:validate-world
                            {world? : World slug or id}
                            {--historical : Run 1347 historical integrity checks}';

    protected $description = 'Validate Dies Irae world integrity (dual authority, vassalage, isolation)';

    public function handle(WorldIntegrityValidator $validator): int
    {
        $token = $this->argument('world');
        if ($token === null) {
            $token = $this->option('historical')
                ? HistoricalPack::SLUG
                : config('game.default_world_slug');
        }

        $world = is_numeric($token)
            ? World::query()->find($token)
            : World::query()->where('slug', $token)->first();

        if ($world === null) {
            $this->error("World not found: {$token}");

            return self::FAILURE;
        }

        $report = $validator->validate($world, (bool) $this->option('historical'));

        $this->info("World {$world->slug} (#{$world->id})");
        $this->line('Issues: '.count($report->issues()));
        $this->line('Errors: '.count($report->errors()));

        foreach ($report->issues() as $issue) {
            $this->line("[{$issue->severity}] {$issue->code}: {$issue->message}");
        }

        if (! $report->passed()) {
            $this->error('Validation failed.');

            return self::FAILURE;
        }

        $this->info('Validation passed.');

        return self::SUCCESS;
    }
}
