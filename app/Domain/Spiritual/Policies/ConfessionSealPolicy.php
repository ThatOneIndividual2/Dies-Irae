<?php

namespace App\Domain\Spiritual\Policies;

use App\Domain\Enums\SpiritualObserverContext;
use DomainException;

final class ConfessionSealPolicy
{
    public function isSealed(array $knowledgeRow): bool
    {
        return (bool) ($knowledgeRow['is_sealed'] ?? false);
    }

    public function mayRevealInContext(string $context, array $knowledgeRow): bool
    {
        if (!$this->isSealed($knowledgeRow)) {
            return true;
        }

        return in_array($context, [
            SpiritualObserverContext::CONFESSOR,
            SpiritualObserverContext::ADMIN,
        ], true);
    }

    /**
     * Confession may never be used as political intelligence.
     */
    public function assertNotPoliticalUse(string $context, array $knowledgeRow): void
    {
        if ($this->isSealed($knowledgeRow) && $context === SpiritualObserverContext::POLITICAL) {
            throw new DomainException('Confession seal forbids political use of this knowledge.');
        }
    }
}
