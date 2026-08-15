<?php

namespace App\Console\Commands;

use App\Domain\Events\EventCatalog;
use App\Domain\Events\EventDefinitionValidator;
use Illuminate\Console\Command;

class ValidateEventsCommand extends Command
{
    protected $signature = 'diesirae:validate-events';

    protected $description = 'Validate data-driven narrative event definitions';

    public function handle(EventCatalog $catalog, EventDefinitionValidator $validator): int
    {
        $errors = $validator->validateCatalog($catalog);
        $this->info('Definitions: '.count($catalog->definitions()));
        if ($errors === []) {
            $this->info('Event catalog is valid.');

            return self::SUCCESS;
        }
        foreach ($errors as $error) {
            $this->error($error);
        }

        return self::FAILURE;
    }
}
