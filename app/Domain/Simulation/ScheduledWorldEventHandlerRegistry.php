<?php

namespace App\Domain\Simulation;

use InvalidArgumentException;

final class ScheduledWorldEventHandlerRegistry
{
    /** @var array<string, ScheduledWorldEventHandler> */
    private array $handlers = [];

    public function register(ScheduledWorldEventHandler $handler): void
    {
        $this->handlers[$handler->eventType()] = $handler;
    }

    public function get(string $eventType): ScheduledWorldEventHandler
    {
        if (!isset($this->handlers[$eventType])) {
            throw new InvalidArgumentException("No handler registered for event type: {$eventType}");
        }

        return $this->handlers[$eventType];
    }

    public function has(string $eventType): bool
    {
        return isset($this->handlers[$eventType]);
    }

    /** @return array<string, ScheduledWorldEventHandler> */
    public function all(): array
    {
        return $this->handlers;
    }
}
