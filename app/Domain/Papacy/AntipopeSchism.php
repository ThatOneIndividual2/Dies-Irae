<?php

namespace App\Domain\Papacy;

final class AntipopeSchism
{
    public string $id;
    public string $recognizedClaimantId;
    public string $rivalClaimantId;
    public string $status = 'open';
    public string $openedDate;
    public ?string $healedDate = null;
    /** @var list<string> */
    public array $rivalElectorIds = [];
    /** @var list<string> */
    public array $rivalSeeIds = [];
    /** @var list<string> */
    public array $rivalRealmIds = [];

    public function __construct(
        string $id,
        string $recognizedClaimantId,
        string $rivalClaimantId,
        string $openedDate
    ) {
        $this->id = $id;
        $this->recognizedClaimantId = $recognizedClaimantId;
        $this->rivalClaimantId = $rivalClaimantId;
        $this->openedDate = $openedDate;
    }

    public function open(): bool
    {
        return $this->status === 'open';
    }
}
