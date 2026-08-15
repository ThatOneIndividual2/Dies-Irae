<?php

namespace App\Domain\Papacy;

use App\Domain\Enums\SecularPressureKind;

final class SecularPressure
{
    public string $id;
    public string $rulerId;
    public ?string $rulerRealmId;
    public string $kind;
    public ?string $candidateId;
    /** @var list<string>|null */
    public ?array $targetElectorIds;
    public int $magnitude;
    public string $date;

    /**
     * @param list<string>|null $targetElectorIds
     */
    public function __construct(
        string $id,
        string $rulerId,
        string $kind,
        int $magnitude,
        string $date,
        ?string $candidateId = null,
        ?array $targetElectorIds = null,
        ?string $rulerRealmId = null
    ) {
        if (!in_array($kind, SecularPressureKind::all(), true)) {
            throw new PapacyException("Unknown secular pressure: {$kind}");
        }

        $this->id = $id;
        $this->rulerId = $rulerId;
        $this->kind = $kind;
        $this->magnitude = $magnitude;
        $this->date = $date;
        $this->candidateId = $candidateId;
        $this->targetElectorIds = $targetElectorIds;
        $this->rulerRealmId = $rulerRealmId;
    }

    public function targets(string $electorId): bool
    {
        if ($this->targetElectorIds === null) {
            return true;
        }

        return in_array($electorId, $this->targetElectorIds, true);
    }
}
