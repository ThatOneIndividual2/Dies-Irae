<?php

namespace App\Actions\Hell;

use App\Domain\Hell\Enums\GrudgeSubjectType;
use App\Models\NamedDemon;
use App\Models\NamedDemonGrudge;
use InvalidArgumentException;

final class RememberNamedDemonGrudge
{
    public function execute(NamedDemon $demon, string $subjectType, string $subjectId, string $reason, int $intensity = 1): NamedDemonGrudge
    {
        if (!in_array($subjectType, GrudgeSubjectType::all(), true)) {
            throw new InvalidArgumentException("Unknown grudge subject {$subjectType}");
        }

        $row = NamedDemonGrudge::query()->firstOrNew([
            'named_demon_id' => $demon->id,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
        ]);
        $row->world_id = $demon->world_id;
        $row->reason = $reason;
        $row->intensity = min(100, (int) ($row->exists ? $row->intensity : 0) + max(1, $intensity));
        $row->save();

        return $row;
    }
}
