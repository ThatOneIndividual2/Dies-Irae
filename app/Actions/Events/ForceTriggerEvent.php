<?php

namespace App\Actions\Events;

use App\Domain\Events\EventCatalog;
use App\Domain\Events\EventConditionEvaluator;
use App\Domain\Events\EventWorldViewFactory;
use App\Domain\Support\LocalInspectGuard;
use App\Models\GameEvent;
use App\Models\World;

final class ForceTriggerEvent
{
    public function __construct(
        private EventCatalog $catalog,
        private EventWorldViewFactory $views,
        private EventConditionEvaluator $conditions,
        private FireCatalogEvent $fire
    ) {
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function execute(World $world, string $definitionKey, string $scopeType, int $scopeId, array $options = []): GameEvent
    {
        LocalInspectGuard::assertMutable();
        $this->catalog->definition($definitionKey);
        $view = $this->views->make($world);

        return $this->fire->execute($world, $definitionKey, $scopeType, $scopeId, array_merge($options, [
            'view' => $view,
            'force' => true,
            'actor_character_id' => $options['actor_character_id'] ?? $this->conditions->actorId($view, $scopeType, $scopeId),
            'nonce' => $options['nonce'] ?? 'force-'.bin2hex(random_bytes(3)),
        ]));
    }
}
