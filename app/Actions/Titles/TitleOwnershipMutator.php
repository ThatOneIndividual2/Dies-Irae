<?php

namespace App\Actions\Titles;

use App\Domain\Enums\AcquisitionType;
use App\Domain\Enums\TitleRank;
use App\Domain\Support\AfterCommit;
use App\Domain\Support\Transactional;
use App\Domain\Support\WorldBoundary;
use App\Events\TitleGranted;
use App\Events\TitleRevoked;
use App\Events\TitleTransferred;
use App\Models\Character;
use App\Models\Title;
use App\Models\TitleOwnership;
use Carbon\CarbonInterface;
use InvalidArgumentException;
use RuntimeException;

final class TitleOwnershipMutator
{
    public function grant(
        Title $title,
        Character $holder,
        string $acquisitionType,
        CarbonInterface $date,
        ?Character $grantedBy = null,
        ?string $eventClass = null
    ): TitleOwnership {
        return $this->assign($title, $holder, $acquisitionType, $date, $grantedBy, $eventClass ?? TitleGranted::class);
    }

    public function transfer(
        Title $title,
        Character $newHolder,
        CarbonInterface $date,
        ?Character $grantedBy = null,
        string $acquisitionType = AcquisitionType::TRANSFER
    ): TitleOwnership {
        return $this->assign($title, $newHolder, $acquisitionType, $date, $grantedBy, TitleTransferred::class);
    }

    public function revoke(Title $title, CarbonInterface $date, ?Character $revokedBy = null): ?TitleOwnership
    {
        return Transactional::run(function () use ($title, $date, $revokedBy) {
            $lockedTitle = Title::query()->whereKey($title->id)->lockForUpdate()->firstOrFail();
            $current = TitleOwnership::query()
                ->where('title_id', $lockedTitle->id)
                ->where('is_current', true)
                ->lockForUpdate()
                ->first();

            if (!$current) {
                return null;
            }

            if ($revokedBy) {
                WorldBoundary::assertSameWorldEntities('title revoke', $lockedTitle, $revokedBy);
            }

            $previousHolderId = $current->holder_character_id;
            $current->lost_date = $date->toDateString();
            $current->is_current = null;
            $current->save();

            AfterCommit::dispatch(function () use ($lockedTitle, $previousHolderId, $revokedBy, $date) {
                event(new TitleRevoked($lockedTitle->id, $previousHolderId, $revokedBy?->id, $date->toDateString()));
            });

            return $current->fresh();
        });
    }

    private function assign(
        Title $title,
        Character $holder,
        string $acquisitionType,
        CarbonInterface $date,
        ?Character $grantedBy,
        string $eventClass
    ): TitleOwnership {
        if (!in_array($acquisitionType, AcquisitionType::all(), true)) {
            throw new InvalidArgumentException("Unknown acquisition type: {$acquisitionType}");
        }

        TitleRank::assertSecular($title->rank);

        return Transactional::run(function () use ($title, $holder, $acquisitionType, $date, $grantedBy, $eventClass) {
            $lockedTitle = Title::query()->whereKey($title->id)->lockForUpdate()->firstOrFail();
            WorldBoundary::assertSameWorldEntities('title ownership', $lockedTitle, $holder);
            if ($grantedBy) {
                WorldBoundary::assertSameWorldEntities('title grantor', $lockedTitle, $grantedBy);
            }

            $current = TitleOwnership::query()
                ->where('title_id', $lockedTitle->id)
                ->where('is_current', true)
                ->lockForUpdate()
                ->first();

            $previousId = null;
            if ($current) {
                if ((int) $current->holder_character_id === (int) $holder->id) {
                    throw new RuntimeException('Character already holds this title.');
                }
                $current->lost_date = $date->toDateString();
                $current->is_current = null;
                $current->save();
                $previousId = $current->id;
            }

            $ownership = TitleOwnership::query()->create([
                'world_id' => $lockedTitle->world_id,
                'title_id' => $lockedTitle->id,
                'holder_character_id' => $holder->id,
                'acquired_date' => $date->toDateString(),
                'lost_date' => null,
                'acquisition_type' => $acquisitionType,
                'is_current' => true,
            ]);

            $activeCount = TitleOwnership::query()
                ->where('title_id', $lockedTitle->id)
                ->where('is_current', true)
                ->count();

            if ($activeCount !== 1) {
                throw new RuntimeException('Title must have exactly one active owner after mutation.');
            }

            AfterCommit::dispatch(function () use ($eventClass, $ownership, $lockedTitle, $holder, $grantedBy, $date, $previousId) {
                event(new $eventClass(
                    $lockedTitle->id,
                    $holder->id,
                    $ownership->id,
                    $grantedBy?->id,
                    $previousId,
                    $date->toDateString()
                ));
            });

            return $ownership;
        });
    }
}
