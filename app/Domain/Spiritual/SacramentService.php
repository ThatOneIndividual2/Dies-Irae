<?php

namespace App\Domain\Spiritual;

use App\Domain\Enums\CanonicalCensure;
use App\Domain\Enums\EucharistStanding;
use App\Domain\Enums\HolyOrdersGrade;
use App\Domain\Enums\RepentanceDisposition;
use App\Domain\Enums\SacramentType;
use App\Domain\Enums\SacramentValidity;
use App\Domain\Spiritual\Policies\SacramentEligibilityPolicy;
use App\Models\Character;
use App\Models\CharacterCanonicalState;
use App\Models\DemonicInfluence;
use App\Models\SacramentRecord;
use App\Models\World;
use Carbon\CarbonInterface;

final class SacramentService
{
    public function __construct(
        private SpiritualStateService $states,
        private SacramentEligibilityPolicy $eligibility,
        private PenanceService $penances,
        private SpiritualVisibilityService $visibility
    ) {
    }

    public function confer(
        World $world,
        string $sacramentType,
        Character $subject,
        CarbonInterface $date,
        ?Character $minister = null,
        ?Character $spouse = null,
        ?string $placeType = null,
        ?int $placeId = null,
        ?string $ordersGrade = null,
        ?array $metadata = null
    ): SacramentRecord {
        [$spiritual, $canonical] = $this->states->ensure($world, $subject);
        $canonical = CharacterCanonicalState::query()->whereKey($canonical->id)->lockForUpdate()->firstOrFail();

        $evaluation = $this->eligibility->evaluate($sacramentType, $subject, $canonical, $minister, $spouse);
        $validity = $evaluation['validity'];

        $record = SacramentRecord::query()->create([
            'world_id' => $world->id,
            'sacrament_type' => $sacramentType,
            'subject_character_id' => $subject->id,
            'minister_character_id' => $minister?->id,
            'spouse_character_id' => $spouse?->id,
            'place_type' => $placeType,
            'place_id' => $placeId,
            'occurred_date' => $date->toDateString(),
            'validity' => $validity,
            'reasons' => $evaluation['reasons'],
            'orders_grade' => $ordersGrade,
            'consequences_applied' => false,
            'metadata' => $metadata,
        ]);

        if (SacramentValidity::confersGrace($validity)) {
            $this->applyConsequences($world, $record, $subject, $canonical, $minister, $spouse, $date, $ordersGrade);
            $record->consequences_applied = true;
            $record->save();
        }

        return $record->fresh();
    }

    private function applyConsequences(
        World $world,
        SacramentRecord $record,
        Character $subject,
        CharacterCanonicalState $canonical,
        ?Character $minister,
        ?Character $spouse,
        CarbonInterface $date,
        ?string $ordersGrade
    ): void {
        $type = $record->sacrament_type;
        $deltas = config('spiritual.sacrament_deltas.'.$type, []);
        if ($deltas !== []) {
            $this->states->applyDeltas($world, $subject, $deltas, 'sacrament:'.$type, $date, SacramentRecord::class, $record->id);
        }

        if ($type === SacramentType::BAPTISM) {
            $canonical->is_baptized = true;
            $canonical->baptism_date = $date->toDateString();
            if ($canonical->censure === CanonicalCensure::NONE) {
                $canonical->eucharist_standing = EucharistStanding::IN_COMMUNION;
            }
            $this->weakenLowDemonicFoothold($subject);
        }

        if ($type === SacramentType::CONFIRMATION) {
            $canonical->is_confirmed = true;
            $canonical->confirmation_date = $date->toDateString();
        }

        if ($type === SacramentType::CONFESSION) {
            $canonical->grave_unconfessed = false;
            if ($canonical->censure === CanonicalCensure::NONE) {
                $canonical->eucharist_standing = EucharistStanding::IN_COMMUNION;
            }
            [$inner] = $this->states->ensure($world, $subject);
            $this->states->setRepentance($inner, RepentanceDisposition::CONTRITE);
            if ($minister) {
                $this->penances->assign($world, $subject, $minister, $record, $date);
                $this->visibility->recordSealedConfession($world, $minister, $subject, $record, $date);
            }
        }

        if ($type === SacramentType::MATRIMONY && $spouse) {
            $canonical->matrimonial_bond_character_id = $spouse->id;
            $canonical->matrimony_date = $date->toDateString();
            [, $spouseCanonical] = $this->states->ensure($world, $spouse);
            $spouseCanonical = CharacterCanonicalState::query()->whereKey($spouseCanonical->id)->lockForUpdate()->firstOrFail();
            $spouseCanonical->matrimonial_bond_character_id = $subject->id;
            $spouseCanonical->matrimony_date = $date->toDateString();
            $spouseCanonical->save();
        }

        if ($type === SacramentType::HOLY_ORDERS) {
            $grade = $ordersGrade ?: HolyOrdersGrade::PRIEST;
            if (HolyOrdersGrade::weight($grade) > HolyOrdersGrade::weight($canonical->holy_orders_grade)) {
                $canonical->holy_orders_grade = $grade;
            }
        }

        if ($type === SacramentType::ANOINTING) {
            $canonical->last_anointing_date = $date->toDateString();
        }

        if ($type === SacramentType::EUCHARIST && $canonical->grave_unconfessed) {
            $canonical->eucharist_standing = EucharistStanding::BARRED_BY_SIN;
        }

        $canonical->save();
    }

    private function weakenLowDemonicFoothold(Character $subject): void
    {
        $influence = DemonicInfluence::query()
            ->where('character_id', $subject->id)
            ->where('is_active', true)
            ->first();

        if ($influence && $influence->stage === 'temptation') {
            $influence->intensity = max(0, (int) $influence->intensity - 20);
            if ($influence->intensity === 0) {
                $influence->is_active = false;
                $influence->ended_on = now()->toDateString();
            }
            $influence->save();
        }
    }
}
