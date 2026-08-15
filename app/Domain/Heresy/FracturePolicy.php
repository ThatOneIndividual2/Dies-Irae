<?php

namespace App\Domain\Heresy;

use App\Domain\Enums\FractureKind;
use InvalidArgumentException;

final class FracturePolicy
{
    public function assertKind(string $kind): void
    {
        if (!in_array($kind, FractureKind::all(), true)) {
            throw new InvalidArgumentException("Unknown fracture kind: {$kind}");
        }
    }

    public function spec(string $kind): array
    {
        $this->assertKind($kind);
        $spec = config('fracture.kinds.'.$kind);
        if (!is_array($spec)) {
            throw new FractureException("No policy for kind {$kind}.");
        }

        return $spec;
    }

    public function allowsSpread(string $kind, string $vector): bool
    {
        return in_array($vector, $this->spec($kind)['spread'] ?? [], true);
    }

    public function allowsChurchResponse(string $kind, string $response): bool
    {
        return in_array($response, $this->spec($kind)['church'] ?? [], true);
    }

    public function allowsSecularResponse(string $kind, string $response): bool
    {
        return in_array($response, $this->spec($kind)['secular'] ?? [], true);
    }

    public function startingVisibility(string $kind): string
    {
        return $this->spec($kind)['starts_visible'] ?? 'secret';
    }

    public function failedSuppressionDelta(string $kind): int
    {
        return (int) ($this->spec($kind)['failed_suppression_delta'] ?? 10);
    }

    public function churchRanksFor(string $response): array
    {
        return config('fracture.church_response_ranks.'.$response, []);
    }
}
