<?php

namespace App\Domain\Church;

use App\Domain\Enums\AppointmentMode;
use App\Domain\Enums\AppointmentStatus;
use App\Domain\Enums\ClergyLegitimacyStatus;
use App\Domain\Enums\OfficeAcquisitionType;
use App\Domain\Enums\PapacyStatus;
use App\Domain\Enums\PapalClaimStatus;
use App\Domain\Enums\RelicAuthenticity;
use App\Domain\Enums\SpiritualOfficeRank;
use App\Domain\HolyOrders\HolyOrderService;
use App\Domain\Sacred\Policies\CanonizationPolicy;
use App\Domain\Support\Transactional;
use App\Domain\Support\WorldBoundary;
use App\Events\CharacterExcommunicated;
use App\Events\ExcommunicationLifted;
use App\Events\HeresyDeclared;
use App\Events\HolyOrderSanctioned;
use App\Events\InterdictLifted;
use App\Events\InterdictPlaced;
use App\Events\RelicAuthenticityRecognized;
use App\Events\SaintRecognized;
use App\Models\Character;
use App\Models\ClergyAppointment;
use App\Models\ExcommunicationState;
use App\Models\ExorcistLegitimacy;
use App\Models\ExtraordinarySpiritualAuthorization;
use App\Models\Heresy;
use App\Models\HolyOrder;
use App\Models\InterdictState;
use App\Models\Papacy;
use App\Models\PapalClaim;
use App\Models\Relic;
use App\Models\Saint;
use App\Models\See;
use App\Models\SpiritualOffice;
use App\Models\SpiritualOfficeHoldership;
use App\Models\Territory;
use Carbon\CarbonInterface;

final class ChurchAuthority
{
    public function __construct(
        private AppointmentPolicy $appointments,
        private ClergyLegitimacy $legitimacy,
        private SpiritualOfficeHoldershipMutator $holderships,
        private SacramentalAuthorityResolver $sacraments,
        private HolyOrderService $holyOrders
    ) {
    }

    public function domainKey(): string
    {
        return 'church';
    }

    public function actorOffice(Character $actor): ?SpiritualOffice
    {
        return $this->appointments->highestCurrentOffice($actor);
    }

    public function assertPower(Character $actor, string $power, ?See $scopeSee = null): SpiritualOffice
    {
        $office = $this->actorOffice($actor);
        if (!$office) {
            throw new ChurchException("Actor holds no spiritual office for {$power}.");
        }

        $allowed = config('church.powers.'.$power, []);
        if (!in_array($office->rank, $allowed, true)) {
            throw new ChurchException("Office {$office->rank} cannot perform {$power}.");
        }

        $universal = config('church.universal_ranks', [SpiritualOfficeRank::POPE]);
        if (in_array($office->rank, $universal, true)) {
            return $office;
        }

        if ($scopeSee && $office->see_id && (int) $office->see_id !== (int) $scopeSee->id) {
            $parentId = $scopeSee->parent_see_id;
            if ((int) $office->see_id !== (int) $parentId) {
                throw new ChurchException("Actor lacks jurisdiction for {$power}.");
            }
        }

        $holdership = SpiritualOfficeHoldership::query()
            ->where('spiritual_office_id', $office->id)
            ->where('holder_character_id', $actor->id)
            ->where('is_current', true)
            ->first();

        if ($holdership && !$this->legitimacy->canExerciseOffice($actor, $holdership)) {
            throw new ChurchException("Actor cannot lawfully exercise office for {$power}.");
        }

        return $office;
    }

    public function excommunicate(
        Character $actor,
        Character $target,
        CarbonInterface $date,
        ?string $reason = null,
        ?See $scopeSee = null
    ): ExcommunicationState {
        WorldBoundary::assertSameWorldEntities('excommunicate', $actor, $target);
        $office = $this->assertPower($actor, 'excommunicate', $scopeSee);

        return Transactional::run(function () use ($actor, $target, $date, $reason, $office) {
            $current = ExcommunicationState::query()
                ->where('character_id', $target->id)
                ->where('is_current', true)
                ->lockForUpdate()
                ->first();

            if ($current) {
                throw new ChurchException('Character is already excommunicated.');
            }

            $state = ExcommunicationState::query()->create([
                'world_id' => $target->world_id,
                'character_id' => $target->id,
                'issued_by_character_id' => $actor->id,
                'issuing_office_id' => $office->id,
                'reason' => $reason,
                'issued_date' => $date->toDateString(),
                'is_current' => true,
            ]);

            event(new CharacterExcommunicated($target->id, $actor->id, $state->id, $date->toDateString()));

            return $state;
        });
    }

    public function liftExcommunication(
        Character $actor,
        Character $target,
        CarbonInterface $date,
        ?See $scopeSee = null
    ): ExcommunicationState {
        WorldBoundary::assertSameWorldEntities('lift excommunication', $actor, $target);
        $this->assertPower($actor, 'lift_excommunication', $scopeSee);

        return Transactional::run(function () use ($target, $date) {
            $current = ExcommunicationState::query()
                ->where('character_id', $target->id)
                ->where('is_current', true)
                ->lockForUpdate()
                ->first();

            if (!$current) {
                throw new ChurchException('Character is not excommunicated.');
            }

            $current->lifted_date = $date->toDateString();
            $current->is_current = null;
            $current->save();

            event(new ExcommunicationLifted($target->id, $current->id, $date->toDateString()));

            return $current->fresh();
        });
    }

    public function placeInterdict(
        Character $actor,
        CarbonInterface $date,
        ?See $see = null,
        ?Territory $territory = null,
        ?string $reason = null
    ): InterdictState {
        if (!$see && !$territory) {
            throw new ChurchException('Interdict requires a see or territory.');
        }

        $subject = $see ?? $territory;
        WorldBoundary::assertSameWorldEntities('interdict', $actor, $subject);
        $office = $this->assertPower($actor, 'interdict', $see);

        return Transactional::run(function () use ($actor, $date, $see, $territory, $reason, $office, $subject) {
            $state = InterdictState::query()->create([
                'world_id' => $subject->world_id,
                'target_type' => $see ? 'see' : 'territory',
                'see_id' => $see?->id,
                'territory_id' => $territory?->id,
                'issued_by_character_id' => $actor->id,
                'issuing_office_id' => $office->id,
                'reason' => $reason,
                'issued_date' => $date->toDateString(),
                'is_current' => true,
            ]);

            event(new InterdictPlaced($state->id, $actor->id, $date->toDateString()));

            return $state;
        });
    }

    public function liftInterdict(Character $actor, InterdictState $state, CarbonInterface $date): InterdictState
    {
        WorldBoundary::assertSameWorldEntities('lift interdict', $actor, $state);
        $see = $state->see_id ? See::query()->find($state->see_id) : null;
        $this->assertPower($actor, 'interdict', $see);

        if (!$state->is_current) {
            throw new ChurchException('Interdict is not current.');
        }

        $state->lifted_date = $date->toDateString();
        $state->is_current = null;
        $state->save();

        event(new InterdictLifted($state->id, $date->toDateString()));

        return $state->fresh();
    }

    public function appointClergy(
        Character $actor,
        SpiritualOffice $office,
        Character $appointee,
        CarbonInterface $date,
        ?string $mode = null,
        string $legitimacy = ClergyLegitimacyStatus::RECOGNIZED
    ): ClergyAppointment {
        WorldBoundary::assertSameWorldEntities('appoint clergy', $actor, $office, $appointee);
        $this->assertPower($actor, 'appoint_clergy', $office->see);

        $resolvedMode = $mode ?? $this->appointments->modeFor($office);
        if (!$this->appointments->actorMayAppoint($actor, $office, $resolvedMode)) {
            throw new ChurchException("Actor cannot appoint under mode {$resolvedMode}.");
        }

        return Transactional::run(function () use ($actor, $office, $appointee, $date, $resolvedMode, $legitimacy) {
            $appointment = ClergyAppointment::query()->create([
                'world_id' => $office->world_id,
                'spiritual_office_id' => $office->id,
                'character_id' => $appointee->id,
                'mode' => $resolvedMode,
                'status' => AppointmentStatus::RECOGNIZED,
                'appointed_by_character_id' => $actor->id,
                'policy_key' => $resolvedMode,
                'appointed_date' => $date->toDateString(),
                'resolved_date' => $date->toDateString(),
            ]);

            $acquisition = $resolvedMode === AppointmentMode::PAPAL
                ? OfficeAcquisitionType::PAPAL_PROVISION
                : OfficeAcquisitionType::APPOINTMENT;

            $this->holderships->appoint(
                $office,
                $appointee,
                $date,
                $acquisition,
                $actor,
                $legitimacy,
                $appointment->id
            );

            return $appointment->fresh();
        });
    }

    public function recordDisputedAppointment(
        Character $firstActor,
        Character $secondActor,
        SpiritualOffice $office,
        Character $firstAppointee,
        Character $secondAppointee,
        CarbonInterface $date
    ): array {
        WorldBoundary::assertSameWorldEntities(
            'disputed appointment',
            $firstActor,
            $secondActor,
            $office,
            $firstAppointee,
            $secondAppointee
        );

        return Transactional::run(function () use ($firstActor, $secondActor, $office, $firstAppointee, $secondAppointee, $date) {
            $a = ClergyAppointment::query()->create([
                'world_id' => $office->world_id,
                'spiritual_office_id' => $office->id,
                'character_id' => $firstAppointee->id,
                'mode' => AppointmentMode::DISPUTED,
                'status' => AppointmentStatus::DISPUTED,
                'appointed_by_character_id' => $firstActor->id,
                'policy_key' => AppointmentMode::DISPUTED,
                'appointed_date' => $date->toDateString(),
            ]);

            $b = ClergyAppointment::query()->create([
                'world_id' => $office->world_id,
                'spiritual_office_id' => $office->id,
                'character_id' => $secondAppointee->id,
                'mode' => AppointmentMode::DISPUTED,
                'status' => AppointmentStatus::DISPUTED,
                'appointed_by_character_id' => $secondActor->id,
                'competing_appointment_id' => $a->id,
                'policy_key' => AppointmentMode::DISPUTED,
                'appointed_date' => $date->toDateString(),
            ]);

            $a->competing_appointment_id = $b->id;
            $a->save();

            return [$a->fresh(), $b->fresh()];
        });
    }

    public function removeClergy(
        Character $actor,
        SpiritualOffice $office,
        CarbonInterface $date
    ): ?SpiritualOfficeHoldership {
        WorldBoundary::assertSameWorldEntities('remove clergy', $actor, $office);
        $this->assertPower($actor, 'remove_clergy', $office->see);

        return $this->holderships->remove($office, $date, $actor);
    }

    public function declareVacancy(SpiritualOffice $office, CarbonInterface $date): ?SpiritualOfficeHoldership
    {
        return $this->holderships->remove($office, $date);
    }

    public function recognizeSaint(
        Character $actor,
        string $key,
        string $name,
        CarbonInterface $date,
        ?Character $subject = null
    ): Saint {
        $office = $this->assertPower($actor, 'recognize_saint');
        if ($subject) {
            WorldBoundary::assertSameWorldEntities('recognize saint', $actor, $subject);
            $existing = Saint::query()->where('character_id', $subject->id)->first();
            try {
                app(CanonizationPolicy::class)->assertReadyForCanonization($subject, $existing);
            } catch (\DomainException $e) {
                throw new ChurchException($e->getMessage());
            }
            if ($existing) {
                $existing->recognition_status = 'canonized';
                $existing->recognized_by_office_id = $office->id;
                $existing->recognized_by_character_id = $actor->id;
                $existing->recognized_date = $date->toDateString();
                $existing->save();
                event(new SaintRecognized($existing->id, $actor->id, $date->toDateString()));

                return $existing->fresh();
            }
        }

        $saint = Saint::query()->create([
            'world_id' => $actor->world_id,
            'character_id' => $subject?->id,
            'recognized_by_office_id' => $office->id,
            'recognized_by_character_id' => $actor->id,
            'key' => $key,
            'name' => $name,
            'recognition_status' => 'canonized',
            'recognized_date' => $date->toDateString(),
        ]);

        event(new SaintRecognized($saint->id, $actor->id, $date->toDateString()));

        return $saint;
    }

    public function recognizeRelicAuthenticity(
        Character $actor,
        Relic $relic,
        string $authenticity,
        CarbonInterface $date
    ): Relic {
        WorldBoundary::assertSameWorldEntities('recognize relic', $actor, $relic);
        $this->assertPower($actor, 'recognize_relic');

        if (!in_array($authenticity, RelicAuthenticity::all(), true)) {
            throw new ChurchException("Unknown relic authenticity: {$authenticity}");
        }

        $relic->authenticity = $authenticity;
        $relic->save();

        event(new RelicAuthenticityRecognized($relic->id, $authenticity, $actor->id, $date->toDateString()));

        return $relic->fresh();
    }

    public function sanctionHolyOrder(
        Character $actor,
        HolyOrder $order,
        CarbonInterface $date
    ): HolyOrder {
        WorldBoundary::assertSameWorldEntities('sanction holy order', $actor, $order);
        $this->assertPower($actor, 'sanction_holy_order');

        $order = $this->holyOrders->recordPapalSanction($order, $actor, $date);

        event(new HolyOrderSanctioned($order->id, $actor->id, $date->toDateString()));

        return $order->fresh();
    }

    public function suppressHolyOrder(
        Character $actor,
        HolyOrder $order,
        CarbonInterface $date
    ): HolyOrder {
        WorldBoundary::assertSameWorldEntities('suppress holy order', $actor, $order);
        $this->assertPower($actor, 'suppress_holy_order');

        return $this->holyOrders->suppress($order, $actor, $date);
    }

    public function withdrawHolyOrderRecognition(
        Character $actor,
        HolyOrder $order,
        CarbonInterface $date
    ): HolyOrder {
        WorldBoundary::assertSameWorldEntities('withdraw holy order recognition', $actor, $order);
        $this->assertPower($actor, 'withdraw_holy_order_recognition');

        return $this->holyOrders->withdrawRecognition($order, $actor, $date);
    }

    public function excommunicateHolyOrder(
        Character $actor,
        HolyOrder $order,
        CarbonInterface $date
    ): HolyOrder {
        WorldBoundary::assertSameWorldEntities('excommunicate holy order', $actor, $order);
        $this->assertPower($actor, 'excommunicate');

        return $this->holyOrders->excommunicateOrder($order, $actor, $date);
    }

    public function declareHeresy(
        Character $actor,
        string $key,
        string $name,
        CarbonInterface $date
    ): Heresy {
        $office = $this->assertPower($actor, 'declare_heresy');
        $faithId = $office->see?->churchProvince?->faith_id
            ?? Papacy::query()->where('world_id', $actor->world_id)->value('faith_id');

        if (!$faithId) {
            throw new ChurchException('Cannot declare heresy without a faith context.');
        }

        $heresy = Heresy::query()->create([
            'world_id' => $actor->world_id,
            'faith_id' => $faithId,
            'key' => $key,
            'name' => $name,
            'judgement' => 'condemned',
            'condemned_by_office_id' => $office->id,
            'condemned_date' => $date->toDateString(),
        ]);

        event(new HeresyDeclared($heresy->id, $actor->id, $date->toDateString()));

        return $heresy;
    }

    public function legitimizeExorcist(
        Character $actor,
        Character $exorcist,
        CarbonInterface $date,
        string $scope = 'general'
    ): ExorcistLegitimacy {
        WorldBoundary::assertSameWorldEntities('legitimize exorcist', $actor, $exorcist);
        $office = $this->assertPower($actor, 'legitimize_exorcist');

        return Transactional::run(function () use ($actor, $exorcist, $date, $scope, $office) {
            $current = ExorcistLegitimacy::query()
                ->where('character_id', $exorcist->id)
                ->where('is_current', true)
                ->lockForUpdate()
                ->first();

            if ($current) {
                $current->revoked_date = $date->toDateString();
                $current->is_current = null;
                $current->save();
            }

            return ExorcistLegitimacy::query()->create([
                'world_id' => $exorcist->world_id,
                'character_id' => $exorcist->id,
                'granted_by_character_id' => $actor->id,
                'granted_by_office_id' => $office->id,
                'scope' => $scope,
                'granted_date' => $date->toDateString(),
                'is_current' => true,
            ]);
        });
    }

    public function authorizeExtraordinaryAction(
        Character $actor,
        Character $subject,
        string $actionKind,
        CarbonInterface $date,
        ?CarbonInterface $expires = null
    ): ExtraordinarySpiritualAuthorization {
        WorldBoundary::assertSameWorldEntities('authorize extraordinary', $actor, $subject);
        $office = $this->assertPower($actor, 'authorize_extraordinary');

        return ExtraordinarySpiritualAuthorization::query()->create([
            'world_id' => $subject->world_id,
            'character_id' => $subject->id,
            'action_kind' => $actionKind,
            'granted_by_character_id' => $actor->id,
            'granted_by_office_id' => $office->id,
            'granted_date' => $date->toDateString(),
            'expires_date' => $expires?->toDateString(),
            'is_current' => true,
        ]);
    }

    public function claimPapacy(
        Character $claimant,
        Papacy $papacy,
        CarbonInterface $date,
        string $status = PapalClaimStatus::ANTIPOPE,
        ?SpiritualOffice $claimantOffice = null
    ): PapalClaim {
        WorldBoundary::assertSameWorldEntities('claim papacy', $claimant, $papacy);

        $claim = PapalClaim::query()->create([
            'world_id' => $papacy->world_id,
            'papacy_id' => $papacy->id,
            'claimant_character_id' => $claimant->id,
            'claimant_office_id' => $claimantOffice?->id,
            'status' => $status,
            'claimed_date' => $date->toDateString(),
            'is_current' => true,
        ]);

        $currentClaims = PapalClaim::query()
            ->where('papacy_id', $papacy->id)
            ->where('is_current', true)
            ->count();

        $papacy->status = $currentClaims > 1 ? PapacyStatus::SCHISM : PapacyStatus::DISPUTED;
        $papacy->save();

        return $claim;
    }

    public function recognizePapalClaimant(
        Character $recognizer,
        PapalClaim $claim,
        CarbonInterface $date
    ): Papacy {
        $papacy = Papacy::query()->findOrFail($claim->papacy_id);
        WorldBoundary::assertSameWorldEntities('recognize papal claimant', $recognizer, $papacy, $claim);

        return Transactional::run(function () use ($claim, $papacy, $date) {
            $claimant = Character::query()->findOrFail($claim->claimant_character_id);
            $office = SpiritualOffice::query()->findOrFail($papacy->papal_office_id);

            $this->holderships->appoint(
                $office,
                $claimant,
                $date,
                OfficeAcquisitionType::ELECTION,
                null,
                ClergyLegitimacyStatus::RECOGNIZED
            );

            $claim->status = PapalClaimStatus::RECOGNIZED;
            $claim->save();

            $papacy->status = PapacyStatus::OCCUPIED;
            $papacy->recognized_claim_id = $claim->id;
            $papacy->save();

            return $papacy->fresh();
        });
    }

    public function declarePapalVacancy(Papacy $papacy, CarbonInterface $date): Papacy
    {
        $office = SpiritualOffice::query()->findOrFail($papacy->papal_office_id);
        $this->holderships->remove($office, $date);

        $papacy->status = PapacyStatus::VACANT;
        $papacy->recognized_claim_id = null;
        $papacy->save();

        PapalClaim::query()
            ->where('papacy_id', $papacy->id)
            ->where('is_current', true)
            ->where('status', PapalClaimStatus::RECOGNIZED)
            ->update([
                'is_current' => null,
                'ended_date' => $date->toDateString(),
            ]);

        return $papacy->fresh();
    }

    public function canPerformSacrament(Character $minister, string $kind): bool
    {
        return $this->sacraments->canPerform($minister, $kind);
    }
}
