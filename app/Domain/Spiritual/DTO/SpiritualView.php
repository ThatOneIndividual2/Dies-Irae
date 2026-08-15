<?php

namespace App\Domain\Spiritual\DTO;

final class SpiritualView
{
    public function __construct(
        public int $characterId,
        public string $context,
        public array $public = [],
        public array $inferable = [],
        public array $clergy = [],
        public array $private = [],
        public array $sealed = [],
        public array $hiddenFacets = [],
        public bool $isAdmin = false
    ) {
    }

    public function toArray(): array
    {
        $payload = [
            'character_id' => $this->characterId,
            'context' => $this->context,
            'public' => $this->public,
            'inferable' => $this->inferable,
            'clergy' => $this->clergy,
            'hidden_facets' => $this->hiddenFacets,
        ];

        if ($this->context === 'self' || $this->isAdmin) {
            $payload['private'] = $this->private;
        }

        if ($this->context === 'confessor' || $this->isAdmin) {
            $payload['sealed'] = $this->sealed;
        }

        if ($this->isAdmin) {
            $payload['admin'] = true;
            $payload['private'] = $this->private;
            $payload['sealed'] = $this->sealed;
        }

        return $payload;
    }

    public function hasExactScore(string $facet): bool
    {
        return array_key_exists($facet, $this->private)
            || ($this->isAdmin && array_key_exists($facet, $this->private));
    }
}
