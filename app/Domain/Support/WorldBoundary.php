<?php

namespace App\Domain\Support;

use InvalidArgumentException;

final class WorldBoundary
{
    public static function assertSameWorld(int $expectedWorldId, ?int $actualWorldId, string $context): void
    {
        if ($actualWorldId === null || $actualWorldId !== $expectedWorldId) {
            throw new InvalidArgumentException("Cross-world relationship rejected: {$context}");
        }
    }

    public static function assertSameWorldEntities(string $context, object ...$entities): int
    {
        $worldId = null;

        foreach ($entities as $entity) {
            if (!isset($entity->world_id)) {
                throw new InvalidArgumentException("Entity missing world_id in {$context}");
            }

            if ($worldId === null) {
                $worldId = (int) $entity->world_id;
                continue;
            }

            self::assertSameWorld($worldId, (int) $entity->world_id, $context);
        }

        if ($worldId === null) {
            throw new InvalidArgumentException("No entities provided for {$context}");
        }

        return $worldId;
    }

    public static function assertSameWorldIds(string $context, int $leftWorldId, int $rightWorldId): void
    {
        self::assertSameWorld($leftWorldId, $rightWorldId, $context);
    }
}
