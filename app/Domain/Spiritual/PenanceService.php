<?php

namespace App\Domain\Spiritual;

use App\Domain\Enums\PenanceStatus;
use App\Domain\Enums\PenanceWorkType;
use App\Domain\Enums\RepentanceDisposition;
use App\Domain\Support\WorldBoundary;
use App\Models\Character;
use App\Models\CharacterSpiritualState;
use App\Models\Penance;
use App\Models\SacramentRecord;
use App\Models\World;
use Carbon\CarbonInterface;

final class PenanceService
{
    public function assign(
        World $world,
        Character $penitent,
        Character $confessor,
        SacramentRecord $confession,
        CarbonInterface $date,
        string $workType = PenanceWorkType::PRAYER,
        ?string $dueDate = null
    ): Penance {
        WorldBoundary::assertSameWorld((int) $world->id, (int) $penitent->world_id, 'penance penitent');
        WorldBoundary::assertSameWorld((int) $world->id, (int) $confessor->world_id, 'penance confessor');

        $penance = Penance::query()->create([
            'world_id' => $world->id,
            'character_id' => $penitent->id,
            'assigned_by_character_id' => $confessor->id,
            'confession_record_id' => $confession->id,
            'work_type' => $workType,
            'status' => PenanceStatus::ASSIGNED,
            'assigned_date' => $date->toDateString(),
            'due_date' => $dueDate,
        ]);

        $this->setRepentance((int) $penitent->id, RepentanceDisposition::PERFORMING_PENANCE);

        return $penance;
    }

    public function complete(Penance $penance, CarbonInterface $date): Penance
    {
        $penance->status = PenanceStatus::COMPLETED;
        $penance->completed_date = $date->toDateString();
        $penance->save();

        $open = Penance::query()
            ->where('character_id', $penance->character_id)
            ->whereIn('status', [PenanceStatus::ASSIGNED, PenanceStatus::IN_PROGRESS])
            ->exists();
        $this->setRepentance(
            (int) $penance->character_id,
            $open ? RepentanceDisposition::PERFORMING_PENANCE : RepentanceDisposition::CONTRITE
        );

        return $penance->fresh();
    }

    public function neglect(Penance $penance): Penance
    {
        $penance->status = PenanceStatus::NEGLECTED;
        $penance->save();

        $this->setRepentance((int) $penance->character_id, RepentanceDisposition::RELAPSED);

        return $penance->fresh();
    }

    private function setRepentance(int $characterId, string $disposition): void
    {
        CharacterSpiritualState::query()
            ->where('character_id', $characterId)
            ->where('is_current', true)
            ->update(['repentance' => $disposition]);
    }
}
