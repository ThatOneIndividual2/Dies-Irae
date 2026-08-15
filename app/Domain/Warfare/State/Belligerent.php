<?php

namespace App\Domain\Warfare\State;

use App\Domain\Support\WorldBoundary;
use App\Domain\Warfare\Enums\BelligerentKind;

final class Belligerent
{
    public function __construct(
        public int $worldId,
        public int $id,
        public string $kind,
        public string $name,
        public int $martialSkill = 10,
    ) {
        if (!in_array($kind, BelligerentKind::all(), true)) {
            throw new \InvalidArgumentException("Unknown belligerent kind: {$kind}");
        }
    }

    public function assertWorld(int $worldId, string $context): void
    {
        WorldBoundary::assertSameWorld($worldId, $this->worldId, $context);
    }
}
