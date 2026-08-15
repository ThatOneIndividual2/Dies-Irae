<?php

namespace App\Actions\Hell;

use App\Domain\Hell\Enums\KnowledgeFacet;
use App\Domain\Hell\Enums\KnowledgeSource;
use App\Models\NamedDemon;
use App\Models\NamedDemonKnowledge;
use InvalidArgumentException;

final class RevealNamedDemonKnowledge
{
    public function execute(NamedDemon $demon, string $observerKey, string $facet, string $source, string $learnedOn): NamedDemonKnowledge
    {
        if (!in_array($facet, KnowledgeFacet::all(), true)) {
            throw new InvalidArgumentException("Unknown knowledge facet {$facet}");
        }
        if (!in_array($source, KnowledgeSource::all(), true)) {
            throw new InvalidArgumentException("Unknown knowledge source {$source}");
        }

        return NamedDemonKnowledge::query()->firstOrCreate(
            [
                'named_demon_id' => $demon->id,
                'observer_key' => $observerKey,
                'facet' => $facet,
            ],
            [
                'world_id' => $demon->world_id,
                'source' => $source,
                'learned_on' => $learnedOn,
            ]
        );
    }
}
