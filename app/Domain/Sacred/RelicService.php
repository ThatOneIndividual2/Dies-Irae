<?php

namespace App\Domain\Sacred;

use App\Domain\Enums\RelicAcquisition;
use App\Domain\Enums\RelicAuthenticity;
use App\Domain\Enums\RelicCategory;
use App\Domain\Enums\RelicCondition;
use App\Domain\Enums\RelicCustodianType;
use App\Domain\Enums\RelicTrueNature;
use App\Domain\Sacred\Policies\RelicVisibilityPolicy;
use App\Domain\Sacred\Ports\SacredAuthorityPort;
use App\Domain\Support\WorldBoundary;
use App\Events\RelicAuthenticityRecognized;
use App\Models\Character;
use App\Models\Relic;
use App\Models\RelicCustody;
use App\Models\RelicEvent;
use App\Models\RelicProvenance;
use App\Models\Saint;
use App\Models\World;
use Carbon\CarbonInterface;
use DomainException;

final class RelicService
{
    public function __construct(
        private RelicVisibilityPolicy $visibility,
        private SacredAuthorityPort $authority
    ) {
    }

    public function register(
        World $world,
        string $key,
        string $name,
        string $category,
        string $trueNature,
        string $claimedAuthenticity,
        ?string $claimedProvenance,
        CarbonInterface $date,
        ?Saint $saint = null,
        ?int $holdingId = null,
        ?int $territoryId = null,
        int $pilgrimageValue = 10
    ): Relic {
        if (!in_array($category, RelicCategory::all(), true)) {
            throw new DomainException("Unknown relic category: {$category}");
        }
        if (!in_array($trueNature, RelicTrueNature::all(), true)) {
            throw new DomainException("Unknown relic nature: {$trueNature}");
        }
        if ($saint) {
            WorldBoundary::assertSameWorld((int) $world->id, (int) $saint->world_id, 'relic saint');
        }

        $relic = Relic::query()->create([
            'world_id' => $world->id,
            'saint_id' => $saint?->id,
            'key' => $key,
            'name' => $name,
            'category' => $category,
            'true_nature' => $trueNature,
            'claimed_authenticity' => $claimedAuthenticity,
            'authenticity' => RelicAuthenticity::UNRECOGNIZED,
            'claimed_provenance' => $claimedProvenance,
            'pilgrimage_value' => $pilgrimageValue,
            'condition' => RelicCondition::INTACT,
            'current_holding_id' => $holdingId,
            'current_territory_id' => $territoryId,
        ]);

        if ($claimedProvenance) {
            RelicProvenance::query()->create([
                'world_id' => $world->id,
                'relic_id' => $relic->id,
                'claim_text' => $claimedProvenance,
                'certainty' => 'asserted',
                'is_current' => true,
            ]);
        }

        $this->recordEvent($world, $relic, 'registered', $date);

        return $relic->fresh();
    }

    public function placeInCustody(
        World $world,
        Relic $relic,
        string $custodianType,
        ?int $custodianId,
        CarbonInterface $date,
        string $acquisition,
        ?int $holdingId = null,
        ?int $territoryId = null,
        ?int $characterId = null,
        ?string $ownerType = null,
        ?int $ownerId = null,
        ?Character $actor = null
    ): RelicCustody {
        WorldBoundary::assertSameWorld((int) $world->id, (int) $relic->world_id, 'relic custody');
        if (!in_array($custodianType, RelicCustodianType::all(), true)) {
            throw new DomainException("Unknown custodian type: {$custodianType}");
        }
        if (!in_array($acquisition, RelicAcquisition::all(), true)) {
            throw new DomainException("Unknown acquisition: {$acquisition}");
        }
        if ($relic->condition === RelicCondition::DESTROYED) {
            throw new DomainException('A destroyed relic cannot change custody.');
        }

        RelicCustody::query()
            ->where('relic_id', $relic->id)
            ->where('is_current', true)
            ->update([
                'is_current' => null,
                'lost_date' => $date->toDateString(),
            ]);

        $custody = RelicCustody::query()->create([
            'world_id' => $world->id,
            'relic_id' => $relic->id,
            'custodian_type' => $custodianType,
            'custodian_id' => $custodianId,
            'holding_id' => $holdingId,
            'territory_id' => $territoryId,
            'character_id' => $characterId,
            'acquisition' => $acquisition,
            'owner_type' => $ownerType ?? $relic->owner_type,
            'owner_id' => $ownerId ?? $relic->owner_id,
            'acquired_date' => $date->toDateString(),
            'is_current' => true,
        ]);

        $relic->current_holding_id = $holdingId;
        $relic->current_territory_id = $territoryId;
        $relic->current_character_id = $characterId;
        if ($ownerType) {
            $relic->owner_type = $ownerType;
            $relic->owner_id = $ownerId;
        }
        $relic->save();

        $this->recordEvent($world, $relic, $acquisition === RelicAcquisition::THEFT ? 'theft' : 'transfer', $date, $actor?->id, [
            'custodian_type' => $custodianType,
            'custodian_id' => $custodianId,
            'acquisition' => $acquisition,
        ]);

        return $custody;
    }

    public function steal(
        World $world,
        Relic $relic,
        Character $thief,
        string $custodianType,
        ?int $custodianId,
        CarbonInterface $date,
        ?int $holdingId = null,
        ?int $territoryId = null
    ): RelicCustody {
        WorldBoundary::assertSameWorld((int) $world->id, (int) $thief->world_id, 'relic thief');

        return $this->placeInCustody(
            $world,
            $relic,
            $custodianType,
            $custodianId,
            $date,
            RelicAcquisition::THEFT,
            $holdingId,
            $territoryId,
            $thief->id,
            $relic->owner_type,
            $relic->owner_id,
            $thief
        );
    }

    public function desecrate(World $world, Relic $relic, Character $actor, CarbonInterface $date): Relic
    {
        WorldBoundary::assertSameWorld((int) $world->id, (int) $actor->world_id, 'relic desecration');
        $relic->condition = RelicCondition::DESECRATED;
        $relic->pilgrimage_value = max(0, (int) $relic->pilgrimage_value - 20);
        $relic->save();
        $this->recordEvent($world, $relic, 'desecration', $date, $actor->id);

        return $relic->fresh();
    }

    public function destroy(World $world, Relic $relic, Character $actor, CarbonInterface $date): Relic
    {
        WorldBoundary::assertSameWorld((int) $world->id, (int) $actor->world_id, 'relic destruction');
        $relic->condition = RelicCondition::DESTROYED;
        $relic->destroyed_date = $date->toDateString();
        $relic->pilgrimage_value = 0;
        $relic->save();

        RelicCustody::query()
            ->where('relic_id', $relic->id)
            ->where('is_current', true)
            ->update([
                'is_current' => null,
                'lost_date' => $date->toDateString(),
            ]);

        $this->recordEvent($world, $relic, 'destruction', $date, $actor->id);

        return $relic->fresh();
    }

    public function dispute(World $world, Relic $relic, string $rivalProvenance, CarbonInterface $date): Relic
    {
        RelicProvenance::query()
            ->where('relic_id', $relic->id)
            ->where('is_current', true)
            ->update(['is_current' => null]);

        RelicProvenance::query()->create([
            'world_id' => $world->id,
            'relic_id' => $relic->id,
            'claim_text' => $rivalProvenance,
            'certainty' => 'contested',
            'is_current' => true,
        ]);

        $relic->authenticity = RelicAuthenticity::DISPUTED;
        $relic->claimed_authenticity = RelicAuthenticity::DISPUTED;
        $relic->save();
        $this->recordEvent($world, $relic, 'dispute', $date);

        return $relic->fresh();
    }

    public function recognizeAuthenticity(World $world, Relic $relic, Character $actor, string $authenticity, CarbonInterface $date): Relic
    {
        WorldBoundary::assertSameWorld((int) $world->id, (int) $relic->world_id, 'relic recognition');
        if (!$this->authority->mayRecognizeRelic($actor)) {
            throw new DomainException('Actor lacks faculty to judge a relic.');
        }
        if (!in_array($authenticity, RelicAuthenticity::all(), true)) {
            throw new DomainException("Unknown authenticity: {$authenticity}");
        }

        $relic->authenticity = $authenticity;
        $relic->save();
        $this->recordEvent($world, $relic, 'recognition', $date, $actor->id, ['authenticity' => $authenticity]);
        event(new RelicAuthenticityRecognized($relic->id, $authenticity, $actor->id, $date->toDateString()));

        return $relic->fresh();
    }

    public function publicView(Relic $relic): array
    {
        return $this->visibility->publicView($relic);
    }

    public function adminView(Relic $relic): array
    {
        return $this->visibility->adminView($relic);
    }

    public function effectivePilgrimageValue(Relic $relic): int
    {
        $value = (int) $relic->pilgrimage_value;
        if ($relic->authenticity === RelicAuthenticity::RECOGNIZED) {
            $value += (int) config('sacred.relic.recognized_pilgrimage_bonus', 20);
        }
        if ($relic->true_nature === RelicTrueNature::FORGED) {
            $value = max(1, $value - (int) config('sacred.relic.forged_pilgrimage_penalty', 10));
        }
        if ($relic->condition === RelicCondition::DESTROYED) {
            return 0;
        }

        return $value;
    }

    private function recordEvent(
        World $world,
        Relic $relic,
        string $type,
        CarbonInterface $date,
        ?int $actorId = null,
        ?array $metadata = null
    ): void {
        RelicEvent::query()->create([
            'world_id' => $world->id,
            'relic_id' => $relic->id,
            'event_type' => $type,
            'actor_character_id' => $actorId,
            'occurred_date' => $date->toDateString(),
            'metadata' => $metadata,
        ]);
    }
}
