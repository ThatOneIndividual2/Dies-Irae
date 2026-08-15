<?php

namespace App\Domain\Church;

use App\Domain\Enums\SeeKind;
use App\Models\See;
use App\Models\SpiritualOffice;
use App\Models\SpiritualOfficeHoldership;

final class DiocesanHierarchy
{
    /**
     * Walk from a see toward the papal see: parish -> diocese -> archdiocese -> pope.
     *
     * @return See[]
     */
    public function chainFrom(See $see, int $maxDepth = 12): array
    {
        $chain = [];
        $current = $see;
        $seen = [];

        for ($i = 0; $i < $maxDepth; $i++) {
            if (isset($seen[$current->id])) {
                break;
            }
            $seen[$current->id] = true;
            $chain[] = $current;

            if (!$current->parent_see_id) {
                break;
            }

            $parent = See::query()->find($current->parent_see_id);
            if (!$parent) {
                break;
            }
            $current = $parent;
        }

        return $chain;
    }

    public function ordinaryOf(See $see): ?SpiritualOfficeHoldership
    {
        $officeId = $see->ordinary_office_id;
        if (!$officeId) {
            $office = SpiritualOffice::query()
                ->where('see_id', $see->id)
                ->where('is_active', true)
                ->orderByDesc('id')
                ->first();
            $officeId = $office?->id;
        }

        if (!$officeId) {
            return null;
        }

        return SpiritualOfficeHoldership::query()
            ->where('spiritual_office_id', $officeId)
            ->where('is_current', true)
            ->first();
    }

    public function superiorSee(See $see): ?See
    {
        if (!$see->parent_see_id) {
            return null;
        }

        return See::query()->find($see->parent_see_id);
    }

    public function isDiocesanKind(string $kind): bool
    {
        return in_array($kind, [
            SeeKind::PAPAL_SEE,
            SeeKind::PATRIARCHATE,
            SeeKind::ARCHDIOCESE,
            SeeKind::DIOCESE,
            SeeKind::PARISH,
        ], true);
    }
}
