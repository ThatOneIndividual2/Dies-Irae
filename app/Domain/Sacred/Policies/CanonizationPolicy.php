<?php

namespace App\Domain\Sacred\Policies;

use App\Domain\Enums\SaintEvidenceType;
use App\Domain\Enums\SaintOrigin;
use App\Domain\Enums\SaintRecognitionStatus;
use App\Models\Character;
use App\Models\Saint;
use App\Models\SaintCult;
use App\Models\SaintEvidence;
use DomainException;

final class CanonizationPolicy
{
    public function assertDead(Character $character): void
    {
        if ($character->is_alive) {
            throw new DomainException('A living person cannot have a canonization cause.');
        }
    }

    public function evidenceWeight(Saint $saint): int
    {
        return (int) SaintEvidence::query()->where('saint_id', $saint->id)->sum('weight');
    }

    public function distinctTypes(Saint $saint): int
    {
        return SaintEvidence::query()
            ->where('saint_id', $saint->id)
            ->select('evidence_type')
            ->distinct()
            ->get()
            ->count();
    }

    public function cultIntensity(Saint $saint): int
    {
        return (int) SaintCult::query()
            ->where('saint_id', $saint->id)
            ->where('is_current', true)
            ->max('intensity');
    }

    public function hasSufficientEvidence(Saint $saint): bool
    {
        $cfg = config('sacred.canonization');

        return $this->evidenceWeight($saint) >= (int) $cfg['min_evidence_weight']
            && $this->distinctTypes($saint) >= (int) $cfg['min_distinct_types']
            && $this->cultIntensity($saint) >= (int) $cfg['min_cult_intensity'];
    }

    public function assertReadyForCanonization(?Character $subject, ?Saint $saint = null): void
    {
        if ($subject === null) {
            return;
        }

        $this->assertDead($subject);
        $row = $saint ?? Saint::query()->where('character_id', $subject->id)->first();
        if ($row === null) {
            throw new DomainException('Deceased persons require an opened cause before recognition.');
        }
        if ($row->origin !== SaintOrigin::CHARACTER) {
            return;
        }
        if (in_array($row->recognition_status, [SaintRecognitionStatus::REJECTED], true)) {
            throw new DomainException('A rejected cause cannot be canonized.');
        }
        if (!$this->hasSufficientEvidence($row)) {
            throw new DomainException('Deceased persons require an evidence trail and local cult before formal sainthood.');
        }
    }

    public function weightFor(string $evidenceType): int
    {
        if (!in_array($evidenceType, SaintEvidenceType::all(), true)) {
            throw new DomainException("Unknown saint evidence: {$evidenceType}");
        }

        return (int) config('sacred.canonization.evidence_weights.'.$evidenceType, 5);
    }
}
