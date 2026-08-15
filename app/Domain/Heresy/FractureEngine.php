<?php

namespace App\Domain\Heresy;

use App\Domain\Church\ChurchAuthority;
use App\Domain\Church\ChurchException;
use App\Domain\Enums\ChurchFractureResponse;
use App\Domain\Enums\DetectionSource;
use App\Domain\Enums\FractureKind;
use App\Domain\Enums\MovementVisibility;
use App\Domain\Enums\PapalClaimStatus;
use App\Domain\Enums\SecularFractureResponse;
use App\Domain\Support\Transactional;
use App\Domain\Support\WorldBoundary;
use App\Models\AntiClericalUnrest;
use App\Models\ApostasyState;
use App\Models\Character;
use App\Models\ChurchFractureResponseRecord;
use App\Models\CultCell;
use App\Models\CultInfiltration;
use App\Models\CultMember;
use App\Models\CultObjective;
use App\Models\CultOrganization;
use App\Models\CultRitual;
use App\Models\Cult;
use App\Models\FalseProphetState;
use App\Models\FractureDetection;
use App\Models\Heresy;
use App\Models\HeresyPresence;
use App\Models\MovementAdherent;
use App\Models\MovementPresence;
use App\Models\MovementSpreadEvent;
use App\Models\Papacy;
use App\Models\PapalClaim;
use App\Models\PopularMovementState;
use App\Models\ReligiousMovement;
use App\Models\Schism;
use App\Models\SchismObedience;
use App\Models\SchismSecularBacker;
use App\Models\SchismSeeAllegiance;
use App\Models\See;
use App\Models\SecularFractureResponseRecord;
use App\Models\SpiritualOffice;
use App\Models\Territory;
use App\Models\TitleOwnership;
use Carbon\CarbonInterface;

final class FractureEngine
{
    public function __construct(
        private FracturePolicy $policy,
        private SpreadVectorGate $spreadGate,
        private ChurchAuthority $church
    ) {
    }

    public function foundMovement(
        string $kind,
        string $key,
        string $name,
        Character $founder,
        Territory $origin,
        CarbonInterface $date,
        array $attributes = []
    ): ReligiousMovement {
        $this->policy->assertKind($kind);
        WorldBoundary::assertSameWorldEntities('found movement', $founder, $origin);

        return Transactional::run(function () use ($kind, $key, $name, $founder, $origin, $date, $attributes) {
            $visibility = $attributes['visibility'] ?? $this->policy->startingVisibility($kind);

            $movement = ReligiousMovement::query()->create([
                'world_id' => $founder->world_id,
                'faith_id' => $attributes['faith_id'] ?? $founder->faith_id,
                'key' => $key,
                'name' => $name,
                'kind' => $kind,
                'visibility' => $visibility,
                'radicalization' => $attributes['radicalization'] ?? 0,
                'founder_character_id' => $founder->id,
                'leader_character_id' => $attributes['leader_character_id'] ?? $founder->id,
                'origin_territory_id' => $origin->id,
                'status' => 'active',
                'founded_date' => $date->toDateString(),
            ]);

            $this->seedPresence($movement, $origin, $date, $visibility, (int) ($attributes['intensity'] ?? config('fracture.spread.base_intensity', 8)));
            $this->addAdherent($movement, $founder, $date, [
                'role' => 'founder',
                'is_clergy' => (bool) ($attributes['founder_is_clergy'] ?? false),
                'is_patron' => (bool) ($attributes['founder_is_patron'] ?? false),
            ]);

            $this->openKindLedger($movement, $founder, $origin, $date, $attributes);

            return $movement->fresh();
        });
    }

    public function seedPresence(
        ReligiousMovement $movement,
        Territory $territory,
        CarbonInterface $date,
        ?string $visibility = null,
        int $intensity = 8
    ): MovementPresence {
        WorldBoundary::assertSameWorldEntities('movement presence', $movement, $territory);

        $current = MovementPresence::query()
            ->where('movement_id', $movement->id)
            ->where('territory_id', $territory->id)
            ->where('is_current', true)
            ->first();

        if ($current) {
            $current->intensity = min(100, (int) $current->intensity + $intensity);
            $current->save();

            return $current;
        }

        return MovementPresence::query()->create([
            'world_id' => $movement->world_id,
            'movement_id' => $movement->id,
            'territory_id' => $territory->id,
            'intensity' => $intensity,
            'visibility' => $visibility ?? $movement->visibility,
            'started_date' => $date->toDateString(),
            'is_current' => true,
        ]);
    }

    public function addAdherent(ReligiousMovement $movement, Character $character, CarbonInterface $date, array $flags = []): MovementAdherent
    {
        WorldBoundary::assertSameWorldEntities('movement adherent', $movement, $character);

        $existing = MovementAdherent::query()
            ->where('movement_id', $movement->id)
            ->where('character_id', $character->id)
            ->where('is_current', true)
            ->first();

        if ($existing) {
            if (!empty($flags['is_clergy'])) {
                $existing->is_clergy = true;
            }
            if (!empty($flags['is_patron'])) {
                $existing->is_patron = true;
            }
            if (!empty($flags['role']) && $flags['role'] !== 'adherent') {
                $existing->role = $flags['role'];
            }
            $existing->save();
            $this->bumpClergyCount($movement, (bool) $existing->is_clergy);

            return $existing;
        }

        $adherent = MovementAdherent::query()->create([
            'world_id' => $movement->world_id,
            'movement_id' => $movement->id,
            'character_id' => $character->id,
            'role' => $flags['role'] ?? 'adherent',
            'is_clergy' => (bool) ($flags['is_clergy'] ?? false),
            'is_patron' => (bool) ($flags['is_patron'] ?? false),
            'joined_date' => $date->toDateString(),
            'is_current' => true,
        ]);

        $this->bumpClergyCount($movement, (bool) $adherent->is_clergy);

        return $adherent;
    }

    public function spread(
        ReligiousMovement $movement,
        Territory $from,
        Territory $to,
        string $vector,
        Character $actor,
        CarbonInterface $date
    ): MovementPresence {
        WorldBoundary::assertSameWorldEntities('spread movement', $movement, $from, $to, $actor);

        if (!$this->policy->allowsSpread($movement->kind, $vector)) {
            throw new FractureException("Kind {$movement->kind} does not spread by {$vector}.");
        }

        $origin = MovementPresence::query()
            ->where('movement_id', $movement->id)
            ->where('territory_id', $from->id)
            ->where('is_current', true)
            ->first();

        if (!$origin) {
            throw new FractureException('Movement has no current presence in the origin territory.');
        }

        if (!$this->spreadGate->canSpread($movement, $origin, $to, $vector, $actor)) {
            throw new FractureException("Spread vector {$vector} is not available between these territories.");
        }

        $presence = $this->seedPresence($movement, $to, $date, $origin->visibility, max(4, intdiv((int) $origin->intensity, 2)));

        MovementSpreadEvent::query()->create([
            'world_id' => $movement->world_id,
            'movement_id' => $movement->id,
            'from_territory_id' => $from->id,
            'to_territory_id' => $to->id,
            'vector' => $vector,
            'magnitude' => $presence->intensity,
            'spread_date' => $date->toDateString(),
        ]);

        if ($movement->kind === FractureKind::DEMONIC_CULT || $movement->kind === FractureKind::CLANDESTINE_CULT) {
            $org = $movement->cultOrganization;
            if ($org) {
                $this->addCell($org, $to, $actor, $date);
            }
        }

        return $presence;
    }

    public function detect(
        ReligiousMovement $movement,
        string $source,
        Character $reporter,
        CarbonInterface $date,
        array $attributes = []
    ): FractureDetection {
        if (!in_array($source, DetectionSource::all(), true)) {
            throw new FractureException("Unknown detection source: {$source}");
        }

        WorldBoundary::assertSameWorldEntities('detect movement', $movement, $reporter);

        $sealed = $source === DetectionSource::CONFESSION;
        $false = $source === DetectionSource::FALSE_ACCUSATION;
        $rumor = $source === DetectionSource::RUMOR;
        $publicSources = config('fracture.detection.public_reveal_sources', []);
        $reveal = !$false && !$sealed && !$rumor && in_array($source, $publicSources, true);

        if (in_array($source, [DetectionSource::DENUNCIATION, DetectionSource::INFORMANT], true)) {
            $reveal = false;
        }

        $outcome = $false ? 'false' : ($rumor ? 'rumor' : ($sealed ? 'sealed' : 'confirmed'));

        $detection = FractureDetection::query()->create([
            'world_id' => $movement->world_id,
            'movement_id' => $movement->id,
            'territory_id' => $attributes['territory_id'] ?? $movement->origin_territory_id,
            'accused_character_id' => $attributes['accused_character_id'] ?? null,
            'reporter_character_id' => $reporter->id,
            'source' => $source,
            'outcome' => $outcome,
            'public_reveal' => $reveal,
            'sealed_confession' => $sealed,
            'detected_date' => $date->toDateString(),
        ]);

        if ($reveal) {
            $this->reveal($movement);
        } elseif ($rumor && $movement->visibility === MovementVisibility::SECRET) {
            $movement->visibility = MovementVisibility::RUMORED;
            $movement->save();
            MovementPresence::query()
                ->where('movement_id', $movement->id)
                ->where('is_current', true)
                ->where('visibility', MovementVisibility::SECRET)
                ->update(['visibility' => MovementVisibility::RUMORED]);
        }

        return $detection;
    }

    public function churchRespond(
        Character $actor,
        ReligiousMovement $movement,
        string $response,
        CarbonInterface $date,
        array $payload = []
    ): ChurchFractureResponseRecord {
        WorldBoundary::assertSameWorldEntities('church fracture response', $actor, $movement);

        if (!in_array($response, ChurchFractureResponse::all(), true)) {
            throw new FractureException("Unknown church response: {$response}");
        }

        if (!$this->policy->allowsChurchResponse($movement->kind, $response)) {
            throw new FractureException("Kind {$movement->kind} cannot be met with {$response}.");
        }

        $office = $this->church->actorOffice($actor);
        $allowedRanks = $this->policy->churchRanksFor($response);
        if (!$office || !in_array($office->rank, $allowedRanks, true)) {
            throw new FractureException("Actor lacks office rank for {$response}.");
        }

        $result = 'applied';
        $delta = 0;

        if ($response === ChurchFractureResponse::THEOLOGICAL_CONDEMNATION) {
            $this->condemnAsHeresy($actor, $movement, $date);
        } elseif ($response === ChurchFractureResponse::EXCOMMUNICATION) {
            $target = $this->leaderOrFounder($movement);
            $this->church->excommunicate($actor, $target, $date, 'fracture:'.$movement->kind);
        } elseif ($response === ChurchFractureResponse::INTERDICT) {
            $territory = Territory::query()->find($movement->origin_territory_id);
            $this->church->placeInterdict($actor, $date, null, $territory, 'fracture:'.$movement->kind);
        } elseif ($response === ChurchFractureResponse::INQUISITORIAL_INVESTIGATION) {
            $this->detect($movement, DetectionSource::INQUISITORIAL_INVESTIGATION, $actor, $date);
        } elseif ($response === ChurchFractureResponse::RECONCILIATION) {
            $result = $this->reconcile($movement, $date);
        } elseif ($response === ChurchFractureResponse::PENANCE) {
            $result = $this->assignPenance($movement);
        } elseif ($response === ChurchFractureResponse::PREACHING) {
            $this->softenPopular($movement, 10);
        } elseif ($response === ChurchFractureResponse::MILITARY_SUPPRESSION) {
            [$result, $delta] = $this->suppress($movement, $date, 'church');
        } elseif ($response === ChurchFractureResponse::LOCAL_SYNOD) {
            $result = $this->conveneSynod($movement);
        } elseif ($response === ChurchFractureResponse::PAPAL_INTERVENTION) {
            $this->reveal($movement);
            $result = 'papal_intervention';
        }

        return ChurchFractureResponseRecord::query()->create([
            'world_id' => $movement->world_id,
            'movement_id' => $movement->id,
            'actor_character_id' => $actor->id,
            'office_id' => $office->id,
            'response' => $response,
            'result' => $result,
            'radicalization_delta' => $delta,
            'response_date' => $date->toDateString(),
            'payload' => $payload,
        ]);
    }

    public function secularRespond(
        Character $actor,
        ReligiousMovement $movement,
        string $response,
        CarbonInterface $date,
        array $payload = []
    ): SecularFractureResponseRecord {
        WorldBoundary::assertSameWorldEntities('secular fracture response', $actor, $movement);

        if (!in_array($response, SecularFractureResponse::all(), true)) {
            throw new FractureException("Unknown secular response: {$response}");
        }

        if (!$this->policy->allowsSecularResponse($movement->kind, $response)) {
            throw new FractureException("Kind {$movement->kind} cannot be met with secular {$response}.");
        }

        if (!$this->isRuler($actor)) {
            throw new FractureException('Only a secular title holder may issue a secular fracture response.');
        }

        $result = 'applied';
        $delta = 0;
        $titleId = TitleOwnership::query()
            ->where('holder_character_id', $actor->id)
            ->where('is_current', true)
            ->value('title_id');

        if ($response === SecularFractureResponse::PATRONIZE) {
            $this->addAdherent($movement, $actor, $date, ['role' => 'patron', 'is_patron' => true]);
            $movement->visibility = MovementVisibility::PUBLIC;
            $movement->save();
            $result = 'patronized';
        } elseif ($response === SecularFractureResponse::SUPPRESS) {
            [$result, $delta] = $this->suppress($movement, $date, 'secular');
        } elseif ($response === SecularFractureResponse::TOLERATE) {
            $result = 'tolerated';
        } elseif ($response === SecularFractureResponse::NEGOTIATE) {
            $this->softenPopular($movement, 5);
            $result = 'negotiated';
        } elseif ($response === SecularFractureResponse::IMPRISON_LEADERS) {
            $result = 'leader_imprisoned';
        } elseif ($response === SecularFractureResponse::CONFISCATE_PROPERTY) {
            $result = 'property_seized';
        } elseif ($response === SecularFractureResponse::EXPEL) {
            $result = 'expelled';
        } elseif ($response === SecularFractureResponse::EXPLOIT) {
            $result = 'exploited';
        }

        return SecularFractureResponseRecord::query()->create([
            'world_id' => $movement->world_id,
            'movement_id' => $movement->id,
            'actor_character_id' => $actor->id,
            'title_id' => $titleId,
            'response' => $response,
            'result' => $result,
            'radicalization_delta' => $delta,
            'response_date' => $date->toDateString(),
            'payload' => $payload,
        ]);
    }

    public function openAntipapalSchism(
        Character $claimant,
        Papacy $papacy,
        CarbonInterface $date,
        ?SpiritualOffice $claimantOffice = null,
        string $key = 'antipapal-schism'
    ): Schism {
        WorldBoundary::assertSameWorldEntities('open schism', $claimant, $papacy);
        if ($claimantOffice) {
            WorldBoundary::assertSameWorldEntities('open schism office', $claimant, $claimantOffice);
        }

        $papacy->loadMissing(['papalSee', 'papalOffice']);
        $see = $papacy->papalSee;
        $origin = ($see && $see->territory_id)
            ? Territory::query()->findOrFail($see->territory_id)
            : Territory::query()->where('world_id', $papacy->world_id)->firstOrFail();

        $movement = $this->foundMovement(
            FractureKind::SCHISM,
            $key,
            'Obedience split',
            $claimant,
            $origin,
            $date,
            ['founder_is_clergy' => true, 'visibility' => MovementVisibility::PUBLIC]
        );

        $recognized = PapalClaim::query()
            ->where('papacy_id', $papacy->id)
            ->where('status', PapalClaimStatus::RECOGNIZED)
            ->where('is_current', true)
            ->first();

        if (!$recognized) {
            $popeHolder = $papacy->papalOffice?->currentHoldership?->holder;
            if ($popeHolder) {
                $recognized = $this->church->claimPapacy(
                    $popeHolder,
                    $papacy,
                    $date,
                    PapalClaimStatus::RECOGNIZED,
                    $papacy->papalOffice
                );
            }
        }

        $rival = $this->church->claimPapacy(
            $claimant,
            $papacy,
            $date,
            PapalClaimStatus::ANTIPOPE,
            $claimantOffice
        );

        $schism = Schism::query()->create([
            'world_id' => $papacy->world_id,
            'movement_id' => $movement->id,
            'papacy_id' => $papacy->id,
            'recognized_claim_id' => $recognized?->id,
            'rival_claim_id' => $rival->id,
            'status' => 'open',
            'opened_date' => $date->toDateString(),
        ]);

        $this->pledgeCharacter($schism, $claimant, $rival, $date);
        if ($recognized && isset($popeHolder) && $popeHolder) {
            $this->pledgeCharacter($schism, $popeHolder, $recognized, $date);
        } elseif ($recognized) {
            $popeHolder = $papacy->papalOffice?->currentHoldership?->holder;
            if ($popeHolder) {
                $this->pledgeCharacter($schism, $popeHolder, $recognized, $date);
            }
        }
        if ($see && $recognized) {
            $this->pledgeSee($schism, $see, $recognized, $date);
        }

        return $schism->fresh();
    }

    public function pledgeCharacter(Schism $schism, Character $character, PapalClaim $claim, CarbonInterface $date): SchismObedience
    {
        WorldBoundary::assertSameWorldEntities('schism obedience', $schism, $character, $claim);

        $current = SchismObedience::query()
            ->where('character_id', $character->id)
            ->where('is_current', true)
            ->first();
        if ($current) {
            $current->is_current = null;
            $current->ended_date = $date->toDateString();
            $current->save();
        }

        return SchismObedience::query()->create([
            'world_id' => $schism->world_id,
            'schism_id' => $schism->id,
            'character_id' => $character->id,
            'papal_claim_id' => $claim->id,
            'pledged_date' => $date->toDateString(),
            'is_current' => true,
        ]);
    }

    public function pledgeSee(Schism $schism, See $see, PapalClaim $claim, CarbonInterface $date): SchismSeeAllegiance
    {
        WorldBoundary::assertSameWorldEntities('schism see', $schism, $see, $claim);

        $current = SchismSeeAllegiance::query()
            ->where('see_id', $see->id)
            ->where('is_current', true)
            ->first();
        if ($current) {
            $current->is_current = null;
            $current->ended_date = $date->toDateString();
            $current->save();
        }

        return SchismSeeAllegiance::query()->create([
            'world_id' => $schism->world_id,
            'schism_id' => $schism->id,
            'see_id' => $see->id,
            'papal_claim_id' => $claim->id,
            'pledged_date' => $date->toDateString(),
            'is_current' => true,
        ]);
    }

    public function rulerBacksClaim(Schism $schism, Character $ruler, PapalClaim $claim, CarbonInterface $date): SchismSecularBacker
    {
        if (!$this->isRuler($ruler)) {
            throw new FractureException('Only a secular title holder may back a schism claimant.');
        }

        WorldBoundary::assertSameWorldEntities('schism backer', $schism, $ruler, $claim);

        return SchismSecularBacker::query()->create([
            'world_id' => $schism->world_id,
            'schism_id' => $schism->id,
            'character_id' => $ruler->id,
            'papal_claim_id' => $claim->id,
            'title_id' => TitleOwnership::query()->where('holder_character_id', $ruler->id)->where('is_current', true)->value('title_id'),
            'backed_date' => $date->toDateString(),
            'is_current' => true,
        ]);
    }

    public function recruitCultMember(CultOrganization $org, Character $character, CarbonInterface $date, ?CultCell $cell = null, string $role = 'initiate'): CultMember
    {
        WorldBoundary::assertSameWorldEntities('cult recruit', $org, $character);

        $existing = CultMember::query()
            ->where('character_id', $character->id)
            ->where('is_current', true)
            ->first();

        if ($existing) {
            if ((int) $existing->organization_id !== (int) $org->id) {
                throw new FractureException('Character already belongs to another cult.');
            }

            return $existing;
        }

        return CultMember::query()->create([
            'world_id' => $org->world_id,
            'organization_id' => $org->id,
            'cell_id' => $cell?->id,
            'character_id' => $character->id,
            'role' => $role,
            'recruited_date' => $date->toDateString(),
            'is_current' => true,
        ]);
    }

    public function performRitual(CultOrganization $org, string $riteKey, CarbonInterface $date, bool $sacrifice = false, int $corruption = 0): CultRitual
    {
        if ($org->kind !== FractureKind::DEMONIC_CULT && $sacrifice) {
            throw new FractureException('Sacrifice hooks belong to demonic cults.');
        }

        return CultRitual::query()->create([
            'world_id' => $org->world_id,
            'organization_id' => $org->id,
            'rite_key' => $riteKey,
            'requires_sacrifice' => $sacrifice,
            'performed_date' => $date->toDateString(),
            'corruption_delta' => $corruption,
        ]);
    }

    public function infiltrate(CultOrganization $org, Character $character, string $targetType, CarbonInterface $date, ?int $targetId = null): CultInfiltration
    {
        $org->infiltrating_church = $targetType === 'see' || $targetType === 'office';
        $org->save();

        return CultInfiltration::query()->create([
            'world_id' => $org->world_id,
            'organization_id' => $org->id,
            'character_id' => $character->id,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'depth' => 1,
            'started_date' => $date->toDateString(),
            'is_current' => true,
        ]);
    }

    public function currentPresence(ReligiousMovement $movement, Territory $territory): ?MovementPresence
    {
        return MovementPresence::query()
            ->where('movement_id', $movement->id)
            ->where('territory_id', $territory->id)
            ->where('is_current', true)
            ->first();
    }

    private function openKindLedger(
        ReligiousMovement $movement,
        Character $founder,
        Territory $origin,
        CarbonInterface $date,
        array $attributes
    ): void {
        if ($movement->kind === FractureKind::DOCTRINAL_HERESY) {
            $heresy = Heresy::query()->create([
                'world_id' => $movement->world_id,
                'faith_id' => $movement->faith_id,
                'movement_id' => $movement->id,
                'key' => $movement->key,
                'name' => $movement->name,
                'doctrine_error_key' => $attributes['doctrine_error_key'] ?? 'unspecified',
                'judgement' => 'named',
            ]);
            HeresyPresence::query()->create([
                'world_id' => $movement->world_id,
                'heresy_id' => $heresy->id,
                'territory_id' => $origin->id,
                'status' => $movement->visibility === MovementVisibility::PUBLIC ? 'public' : 'hidden',
                'intensity' => 10,
                'started_date' => $date->toDateString(),
                'is_current' => true,
            ]);
        }

        if ($movement->kind === FractureKind::APOSTASY) {
            ApostasyState::query()->create([
                'world_id' => $movement->world_id,
                'movement_id' => $movement->id,
                'character_id' => $founder->id,
                'from_faith_key' => $attributes['from_faith_key'] ?? 'latin_christianity',
                'cause' => $attributes['cause'] ?? 'renunciation',
                'apostasized_date' => $date->toDateString(),
                'is_current' => true,
            ]);
        }

        if ($movement->kind === FractureKind::POPULAR_MOVEMENT) {
            PopularMovementState::query()->create([
                'world_id' => $movement->world_id,
                'movement_id' => $movement->id,
                'orthodoxy_stance' => $attributes['orthodoxy_stance'] ?? 'reform',
                'fervor' => $attributes['fervor'] ?? 20,
                'violence' => 0,
                'church_regularized' => false,
            ]);
        }

        if ($movement->kind === FractureKind::ANTI_CLERICAL_UNREST) {
            AntiClericalUnrest::query()->create([
                'world_id' => $movement->world_id,
                'movement_id' => $movement->id,
                'territory_id' => $origin->id,
                'intensity' => $attributes['intensity'] ?? 20,
                'status' => 'open',
                'started_date' => $date->toDateString(),
            ]);
        }

        if ($movement->kind === FractureKind::FALSE_PROPHET_MOVEMENT) {
            FalseProphetState::query()->create([
                'world_id' => $movement->world_id,
                'movement_id' => $movement->id,
                'prophet_character_id' => $founder->id,
                'claimed_revelation' => $attributes['claimed_revelation'] ?? 'private vision',
                'following' => $attributes['following'] ?? 20,
                'examination' => 'unexamined',
            ]);
        }

        if (in_array($movement->kind, [FractureKind::CLANDESTINE_CULT, FractureKind::DEMONIC_CULT], true)) {
            $org = CultOrganization::query()->create([
                'world_id' => $movement->world_id,
                'movement_id' => $movement->id,
                'faction_id' => $attributes['faction_id'] ?? null,
                'leader_character_id' => $founder->id,
                'kind' => $movement->kind,
                'secrecy' => $attributes['secrecy'] ?? 70,
                'discovery_state' => 'hidden',
                'hidden_objective' => $attributes['hidden_objective'] ?? 'unknown',
            ]);
            CultObjective::query()->create([
                'world_id' => $movement->world_id,
                'organization_id' => $org->id,
                'objective_key' => $attributes['objective_key'] ?? 'remain_hidden',
                'status' => 'hidden',
            ]);
            $cell = $this->addCell($org, $origin, $founder, $date);
            $this->recruitCultMember($org, $founder, $date, $cell, 'leader');

            if ($movement->kind === FractureKind::DEMONIC_CULT && !empty($attributes['faction_id'])) {
                Cult::query()->create([
                    'world_id' => $movement->world_id,
                    'territory_id' => $origin->id,
                    'faction_id' => $attributes['faction_id'],
                    'organization_id' => $org->id,
                    'kind' => 'demonic',
                    'discovery_state' => 'hidden',
                    'activity' => 10,
                    'strength' => 10,
                    'revealed' => false,
                    'destroyed' => false,
                ]);
            }
        }
    }

    private function addCell(CultOrganization $org, Territory $territory, Character $leader, CarbonInterface $date): CultCell
    {
        $existing = CultCell::query()
            ->where('organization_id', $org->id)
            ->where('territory_id', $territory->id)
            ->where('is_current', true)
            ->first();

        if ($existing) {
            $existing->strength = min(100, (int) $existing->strength + 5);
            $existing->save();

            return $existing;
        }

        return CultCell::query()->create([
            'world_id' => $org->world_id,
            'organization_id' => $org->id,
            'territory_id' => $territory->id,
            'leader_character_id' => $leader->id,
            'strength' => 10,
            'secrecy' => $org->secrecy,
            'discovery_state' => $org->discovery_state,
            'is_current' => true,
        ]);
    }

    private function reveal(ReligiousMovement $movement): void
    {
        $movement->visibility = MovementVisibility::PUBLIC;
        $movement->save();

        MovementPresence::query()->where('movement_id', $movement->id)->where('is_current', true)
            ->update(['visibility' => MovementVisibility::PUBLIC]);

        $org = $movement->cultOrganization;
        if ($org) {
            $org->discovery_state = 'revealed';
            $org->secrecy = max(0, (int) $org->secrecy - 40);
            $org->save();
            CultCell::query()->where('organization_id', $org->id)->update(['discovery_state' => 'revealed']);
        }
    }

    private function condemnAsHeresy(Character $actor, ReligiousMovement $movement, CarbonInterface $date): void
    {
        if ($movement->kind !== FractureKind::DOCTRINAL_HERESY && $movement->kind !== FractureKind::FALSE_PROPHET_MOVEMENT) {
            throw new FractureException('Theological condemnation is not a valid act against this kind.');
        }

        try {
            $this->church->assertPower($actor, 'declare_heresy');
        } catch (ChurchException $e) {
            throw new FractureException($e->getMessage(), 0, $e);
        }

        $heresy = $movement->heresy;
        if (!$heresy) {
            $heresy = Heresy::query()->create([
                'world_id' => $movement->world_id,
                'faith_id' => $movement->faith_id,
                'movement_id' => $movement->id,
                'key' => $movement->key.'-condemned',
                'name' => $movement->name,
                'judgement' => 'condemned',
                'condemned_date' => $date->toDateString(),
            ]);
        } else {
            $heresy->judgement = 'condemned';
            $heresy->condemned_date = $date->toDateString();
            $heresy->save();
        }

        if ($movement->kind === FractureKind::FALSE_PROPHET_MOVEMENT && $movement->falseProphetState) {
            $movement->falseProphetState->examination = 'condemned';
            $movement->falseProphetState->save();
        }
    }

    private function reconcile(ReligiousMovement $movement, CarbonInterface $date): string
    {
        if ($movement->kind === FractureKind::SCHISM) {
            $schism = $movement->schism;
            if ($schism) {
                $schism->status = 'healed';
                $schism->healed_date = $date->toDateString();
                $schism->save();
            }
            $movement->status = 'healed';
            $movement->ended_date = $date->toDateString();
            $movement->save();

            return 'schism_healed';
        }

        if ($movement->kind === FractureKind::DOCTRINAL_HERESY) {
            $movement->status = 'reconciled';
            $movement->ended_date = $date->toDateString();
            $movement->save();
            if ($movement->heresy) {
                $movement->heresy->judgement = 'reconciled';
                $movement->heresy->save();
            }

            return 'heresy_reconciled';
        }

        if ($movement->kind === FractureKind::APOSTASY) {
            ApostasyState::query()->where('movement_id', $movement->id)->where('is_current', true)
                ->update(['is_current' => null, 'reconciled_date' => $date->toDateString()]);
            $movement->status = 'reconciled';
            $movement->save();

            return 'apostasy_reconciled';
        }

        if ($movement->kind === FractureKind::POPULAR_MOVEMENT) {
            throw new FractureException('A reform movement is regularized, not reconciled from heresy.');
        }

        throw new FractureException("Kind {$movement->kind} has no reconciliation path.");
    }

    private function assignPenance(ReligiousMovement $movement): string
    {
        if ($movement->kind === FractureKind::POPULAR_MOVEMENT) {
            $this->softenPopular($movement, 8);
            if ($movement->popularState) {
                $movement->popularState->church_regularized = true;
                $movement->popularState->save();
            }

            return 'regularized';
        }

        return 'penance_assigned';
    }

    private function softenPopular(ReligiousMovement $movement, int $amount): void
    {
        if ($movement->popularState) {
            $movement->popularState->fervor = max(0, (int) $movement->popularState->fervor - $amount);
            $movement->popularState->save();
        }

        $presence = MovementPresence::query()->where('movement_id', $movement->id)->where('is_current', true)->first();
        if ($presence) {
            $presence->intensity = max(0, (int) $presence->intensity - $amount);
            $presence->save();
        }
    }

    private function suppress(ReligiousMovement $movement, CarbonInterface $date, string $by): array
    {
        $presence = MovementPresence::query()->where('movement_id', $movement->id)->where('is_current', true)->first();
        $threshold = (int) config('fracture.suppression.success_if_intensity_below', 15);
        $intensity = (int) ($presence->intensity ?? 50);

        if ($intensity < $threshold) {
            $movement->status = 'suppressed';
            $movement->ended_date = $date->toDateString();
            $movement->save();

            return ['suppressed', 0];
        }

        $delta = $this->policy->failedSuppressionDelta($movement->kind);
        $movement->radicalization = min(100, (int) $movement->radicalization + $delta);
        $movement->save();

        if (in_array($movement->kind, config('fracture.suppression.spawn_unrest_from', []), true) && $presence) {
            $this->foundMovement(
                FractureKind::ANTI_CLERICAL_UNREST,
                $movement->key.'-unrest',
                $movement->name.' unrest',
                $this->leaderOrFounder($movement),
                Territory::query()->findOrFail($presence->territory_id),
                $date,
                ['intensity' => min(100, $delta + 10)]
            );
        }

        if (in_array($movement->kind, config('fracture.suppression.deepen_secrecy_for', []), true)) {
            $org = $movement->cultOrganization;
            if ($org) {
                $org->secrecy = min(100, (int) $org->secrecy + 15);
                $org->discovery_state = 'hidden';
                $org->save();
            }
            $movement->visibility = MovementVisibility::SECRET;
            $movement->save();
        }

        if ($movement->kind === FractureKind::FALSE_PROPHET_MOVEMENT && $movement->falseProphetState) {
            $movement->falseProphetState->following = (int) $movement->falseProphetState->following + 10;
            $movement->falseProphetState->save();
        }

        if ($movement->popularState) {
            $movement->popularState->violence = min(100, (int) $movement->popularState->violence + $delta);
            $movement->popularState->orthodoxy_stance = 'radicalized';
            $movement->popularState->save();
        }

        return ['failed_radicalized', $delta];
    }

    private function conveneSynod(ReligiousMovement $movement): string
    {
        if ($movement->kind === FractureKind::FALSE_PROPHET_MOVEMENT && $movement->falseProphetState) {
            $movement->falseProphetState->examination = 'examined';
            $movement->falseProphetState->save();

            return 'examined';
        }

        if ($movement->kind === FractureKind::POPULAR_MOVEMENT) {
            return 'examined';
        }

        return 'recorded';
    }

    private function bumpClergyCount(ReligiousMovement $movement, bool $isClergy): void
    {
        if (!$isClergy) {
            return;
        }

        $presence = MovementPresence::query()
            ->where('movement_id', $movement->id)
            ->where('territory_id', $movement->origin_territory_id)
            ->where('is_current', true)
            ->first();

        if ($presence) {
            $presence->clergy_adherents = max(1, (int) $presence->clergy_adherents);
            $presence->save();
        }
    }

    private function leaderOrFounder(ReligiousMovement $movement): Character
    {
        $id = $movement->leader_character_id ?? $movement->founder_character_id;

        return Character::query()->findOrFail($id);
    }

    private function isRuler(Character $character): bool
    {
        return TitleOwnership::query()
            ->where('holder_character_id', $character->id)
            ->where('is_current', true)
            ->exists();
    }
}
