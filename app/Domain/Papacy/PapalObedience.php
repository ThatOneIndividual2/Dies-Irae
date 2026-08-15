<?php

namespace App\Domain\Papacy;

use App\Domain\Enums\PapalObedienceSubject;

final class PapalObedience
{
    public string $subjectKind;
    public string $subjectId;
    public string $claimantId;
    public string $date;

    public function __construct(string $subjectKind, string $subjectId, string $claimantId, string $date)
    {
        if (!in_array($subjectKind, PapalObedienceSubject::all(), true)) {
            throw new PapacyException("Unknown obedience subject: {$subjectKind}");
        }

        $this->subjectKind = $subjectKind;
        $this->subjectId = $subjectId;
        $this->claimantId = $claimantId;
        $this->date = $date;
    }
}
