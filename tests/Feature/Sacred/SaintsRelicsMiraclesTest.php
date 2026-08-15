<?php

namespace Tests\Feature\Sacred;

use App\Actions\Sacred\ApplyPilgrimageTraffic;
use App\Actions\Sacred\CompletePilgrimage;
use App\Actions\Sacred\ConfirmLocalCultus;
use App\Actions\Sacred\DisputeRelic;
use App\Actions\Sacred\EstablishPilgrimageRoute;
use App\Actions\Sacred\InterpretMiracle;
use App\Actions\Sacred\OpenSaintCause;
use App\Actions\Sacred\RecognizeMiracle;
use App\Actions\Sacred\RecognizeSaintCause;
use App\Actions\Sacred\RecordMiracleClaim;
use App\Actions\Sacred\RegisterHistoricalSaint;
use App\Actions\Sacred\RegisterRelic;
use App\Actions\Sacred\StealRelic;
use App\Actions\Sacred\SubmitSaintEvidence;
use App\Actions\Sacred\TransferRelic;
use App\Domain\Enums\MiracleCategory;
use App\Domain\Enums\MiracleCausation;
use App\Domain\Enums\MiracleReading;
use App\Domain\Enums\MiracleStatus;
use App\Domain\Enums\RelicAcquisition;
use App\Domain\Enums\RelicAuthenticity;
use App\Domain\Enums\RelicCategory;
use App\Domain\Enums\RelicCustodianType;
use App\Domain\Enums\RelicTrueNature;
use App\Domain\Enums\SaintEvidenceType;
use App\Domain\Enums\SaintRecognitionStatus;
use App\Domain\Enums\SettlementKind;
use App\Domain\Population\PopulationCohorts;
use App\Domain\Sacred\Ports\InMemorySacredAuthority;
use App\Domain\Sacred\Ports\PlagueTravelPort;
use App\Domain\Sacred\Ports\RecordingPlagueTravelPort;
use App\Domain\Sacred\Ports\SacredAuthorityPort;
use App\Domain\Sacred\RelicService;
use App\Domain\Sacred\SacredInspectionService;
use App\Models\Character;
use App\Models\CharacterSpiritualState;
use App\Models\RelicCustody;
use App\Models\Territory;
use App\Models\World;
use Carbon\Carbon;
use DomainException;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Feature\LaravelTestCase;
use Tests\Support\WorldFixture;

class SaintsRelicsMiraclesTest extends LaravelTestCase
{
    use DatabaseTransactions;

    private function world(): World
    {
        $date = '1348-01-01';
        $suffix = substr(bin2hex(random_bytes(4)), 0, 8);
        $row = [
            'slug' => 'sacred-'.$suffix,
            'name' => 'Sacred Test '.$suffix,
            'status' => 'running',
            'start_date' => $date,
            'created_at' => now(),
            'updated_at' => now(),
        ];
        foreach ([
            'game_date' => $date,
            'current_date' => $date,
            'game_speed' => 2,
            'simulation_seed' => bin2hex(random_bytes(8)),
            'political_map_version' => 1,
        ] as $column => $value) {
            if (Schema::hasColumn('worlds', $column)) {
                $row[$column] = $value;
            }
        }

        return World::query()->findOrFail(DB::table('worlds')->insertGetId($row));
    }

    private function person(World $world, bool $alive = true): Character
    {
        return Character::query()->create([
            'world_id' => $world->id,
            'key' => 'c-'.substr(bin2hex(random_bytes(6)), 0, 12),
            'first_name' => 'Test',
            'sex' => 'male',
            'birth_date' => '1320-01-01',
            'death_date' => $alive ? null : '1347-12-01',
            'is_alive' => $alive,
            'legitimacy_status' => 'legitimate',
            'prestige' => 0,
            'health' => 40,
        ]);
    }

    private function land(World $world, string $name): Territory
    {
        return Territory::query()->create([
            'world_id' => $world->id,
            'key' => 't-'.substr(bin2hex(random_bytes(4)), 0, 8),
            'name' => $name,
            'territory_type' => 'county',
        ]);
    }

    private function clergy(): InMemorySacredAuthority
    {
        $port = app(SacredAuthorityPort::class);
        $this->assertInstanceOf(InMemorySacredAuthority::class, $port);

        return $port;
    }

    public function test_historical_saint_has_feast_patronage_and_shrine(): void
    {
        $world = $this->world();
        $land = $this->land($world, 'Paris');
        $saint = app(RegisterHistoricalSaint::class)->execute(
            $world,
            'st-denis',
            'Denis',
            Carbon::parse('1348-01-01'),
            '10-09',
            ['paris', 'headaches'],
            $land->id
        );

        $this->assertSame(SaintRecognitionStatus::CULTUS, $saint->recognition_status);
        $this->assertSame('historical', $saint->origin);
        $this->assertSame('10-09', $saint->feast_day);
        $this->assertGreaterThan(0, $saint->patronages()->count());
        $this->assertGreaterThan(0, $saint->shrines()->count());
    }

    public function test_living_person_cannot_open_a_cause(): void
    {
        $world = $this->world();
        $living = $this->person($world, true);

        $this->expectException(DomainException::class);
        app(OpenSaintCause::class)->execute($world, $living, 'living-cause', Carbon::parse('1348-02-01'));
    }

    public function test_saint_recognition_follows_evidence_trail(): void
    {
        $world = $this->world();
        $dead = $this->person($world, false);
        $bishop = $this->person($world);
        $this->clergy()->grant($bishop);
        $land = $this->land($world, 'Rouen');
        $date = Carbon::parse('1348-03-01');

        $this->expectException(DomainException::class);
        $cause = app(OpenSaintCause::class)->execute($world, $dead, 'martyr-joan', $date);
        app(RecognizeSaintCause::class)->execute($world, $cause, $bishop, $date);
    }

    public function test_canonization_after_cult_and_evidence(): void
    {
        $world = $this->world();
        $dead = $this->person($world, false);
        $bishop = $this->person($world);
        $this->clergy()->grant($bishop);
        $land = $this->land($world, 'Orleans');
        $date = Carbon::parse('1348-04-01');

        $saint = app(OpenSaintCause::class)->execute($world, $dead, 'martyr-joan', $date);
        app(SubmitSaintEvidence::class)->execute($world, $saint, SaintEvidenceType::MARTYRDOM, $date);
        app(SubmitSaintEvidence::class)->execute($world, $saint, SaintEvidenceType::MIRACLE_CLAIM, $date);
        app(ConfirmLocalCultus::class)->execute($world, $saint, $bishop, (int) $land->id, $date);
        $recognized = app(RecognizeSaintCause::class)->execute($world, $saint, $bishop, $date);

        $this->assertSame(SaintRecognitionStatus::CANONIZED, $recognized->recognition_status);
        $this->assertSame($dead->id, $recognized->character_id);
        $payload = app(SacredInspectionService::class)->inspectSaint($recognized);
        $this->assertTrue($payload['ready_for_canonization']);
    }

    public function test_authentic_and_forged_relics_are_not_the_same_fact(): void
    {
        $world = $this->world();
        $bishop = $this->person($world);
        $this->clergy()->grant($bishop);
        $date = Carbon::parse('1348-05-01');
        $saint = app(RegisterHistoricalSaint::class)->execute($world, 'st-martin', 'Martin', $date);

        $authentic = app(RegisterRelic::class)->execute(
            $world, 'martin-cloak', 'Cloak of Martin', RelicCategory::ASSOCIATED_OBJECT,
            RelicTrueNature::AUTHENTIC, RelicAuthenticity::UNRECOGNIZED, 'given at Amiens', $date, $saint, null, null, 40
        );
        $forged = app(RegisterRelic::class)->execute(
            $world, 'martin-cloak-fair', 'Cloak of Martin', RelicCategory::ASSOCIATED_OBJECT,
            RelicTrueNature::FORGED, RelicAuthenticity::RECOGNIZED, 'bought at a fair', $date, $saint, null, null, 40
        );

        app(\App\Domain\Sacred\RelicService::class)->recognizeAuthenticity(
            $world, $forged, $bishop, RelicAuthenticity::RECOGNIZED, $date
        );
        $forged = $forged->fresh();

        $public = app(RelicService::class)->publicView($forged);
        $this->assertArrayNotHasKey('true_nature', $public);
        $this->assertSame(RelicAuthenticity::RECOGNIZED, $forged->authenticity);
        $this->assertSame(RelicTrueNature::FORGED, $forged->makeVisible(['true_nature'])->true_nature);
        $this->assertSame(RelicTrueNature::AUTHENTIC, $authentic->makeVisible(['true_nature'])->true_nature);
    }

    public function test_theft_moves_custody_not_ownership(): void
    {
        $world = $this->world();
        $abbot = $this->person($world);
        $thief = $this->person($world);
        $date = Carbon::parse('1348-06-01');
        $relic = app(RegisterRelic::class)->execute(
            $world, 'holy-nail', 'Holy Nail', RelicCategory::SACRED_OBJECT,
            RelicTrueNature::DOUBTFUL, RelicAuthenticity::UNRECOGNIZED, 'from the treasury', $date
        );
        app(TransferRelic::class)->execute(
            $world, $relic, RelicCustodianType::MONASTERY, 4, $date,
            RelicAcquisition::DEPOSIT, null, null, $abbot->id,
            RelicCustodianType::MONASTERY, 4
        );
        $stolen = app(StealRelic::class)->execute(
            $world, $relic->fresh(), $thief, RelicCustodianType::PRIVATE_CHARACTER, $date, $thief->id, null, null
        );

        $this->assertSame(RelicAcquisition::THEFT, $stolen->acquisition);
        $this->assertSame($thief->id, $stolen->character_id);
        $this->assertSame(RelicCustodianType::MONASTERY, $relic->fresh()->owner_type);
        $this->assertSame(4, (int) $relic->fresh()->owner_id);
        $this->assertSame(1, RelicCustody::query()->where('relic_id', $relic->id)->where('is_current', true)->count());
    }

    public function test_disputed_relic_keeps_competing_provenance(): void
    {
        $world = $this->world();
        $date = Carbon::parse('1348-06-15');
        $relic = app(RegisterRelic::class)->execute(
            $world, 'two-heads', 'Head of John', RelicCategory::BODILY,
            RelicTrueNature::DOUBTFUL, RelicAuthenticity::UNRECOGNIZED, 'kept at Amiens', $date
        );
        $disputed = app(DisputeRelic::class)->execute($world, $relic, 'kept at Constantinople', $date);
        $this->assertSame(RelicAuthenticity::DISPUTED, $disputed->authenticity);
        $this->assertGreaterThan(1, $disputed->provenances()->count());
    }

    public function test_pilgrimage_changes_faith_income_and_prestige(): void
    {
        $world = $this->world();
        $pilgrim = $this->person($world);
        $from = $this->land($world, 'Lyon');
        $to = $this->land($world, 'Le Puy');
        $saint = app(RegisterHistoricalSaint::class)->execute(
            $world, 'st-michel', 'Michael', Carbon::parse('1348-07-01'), '09-29', ['soldiers'], $to->id
        );
        $route = app(EstablishPilgrimageRoute::class)->execute(
            $world, 'lyon-le-puy', 'Road to Le Puy', 'saint', (int) $saint->id, [$from->id, $to->id], $from->id
        );
        $beforePrestige = (int) $pilgrim->prestige;
        $journey = app(CompletePilgrimage::class)->execute(
            $world, $pilgrim, $route, Carbon::parse('1348-07-02'), Carbon::parse('1348-07-20')
        );

        $this->assertSame('completed', $journey->status);
        $this->assertGreaterThan($beforePrestige, (int) $pilgrim->fresh()->prestige);
        $state = CharacterSpiritualState::query()->where('character_id', $pilgrim->id)->where('is_current', true)->first();
        $this->assertGreaterThan(40, $state->faith);
        $traffic = app(ApplyPilgrimageTraffic::class)->execute($world, $route, 40, Carbon::parse('1348-07-21'), $to->id);
        $this->assertGreaterThan(0, $traffic->income_delta);
        $this->assertGreaterThan(0, $traffic->metadata['legitimacy_delta']);
    }

    public function test_plague_spreads_along_pilgrimage_traffic(): void
    {
        $world = $this->world();
        $from = $this->land($world, 'Origin');
        $to = $this->land($world, 'Shrine');
        $saint = app(RegisterHistoricalSaint::class)->execute($world, 'st-roch', 'Roch', Carbon::parse('1348-08-01'), null, ['plague'], $to->id);
        $route = app(EstablishPilgrimageRoute::class)->execute(
            $world, 'plague-road', 'Shrine road', 'saint', (int) $saint->id, [$from->id, $to->id], $from->id
        );
        app(ApplyPilgrimageTraffic::class)->execute($world, $route, 80, Carbon::parse('1348-08-02'));

        $port = app(PlagueTravelPort::class);
        $this->assertInstanceOf(RecordingPlagueTravelPort::class, $port);
        $this->assertNotEmpty($port->openedLinks());

        $engine = WorldFixture::engine(null, (int) $world->id);
        $fromKey = 'territory-'.$from->id;
        $toKey = 'territory-'.$to->id;
        WorldFixture::city($engine, $fromKey, 'Origin');
        WorldFixture::town($engine, $toKey, 'Shrine', SettlementKind::MONASTERY, new PopulationCohorts(8, 220, 40, 180, 20));
        $port->applyToEngine($engine);
        $engine->seedPlague($fromKey, 1000);
        $engine->tick(1);

        $shrine = $engine->settlement($toKey);
        $this->assertGreaterThan(0, $shrine->incubating + $shrine->infectious);
    }

    public function test_disputed_miracle_can_be_recognized_without_proving_causation(): void
    {
        $world = $this->world();
        $bishop = $this->person($world);
        $physician = $this->person($world);
        $sick = $this->person($world);
        $this->clergy()->grant($bishop);
        $date = Carbon::parse('1348-09-01');
        $saint = app(RegisterHistoricalSaint::class)->execute($world, 'st-luke', 'Luke', $date);
        $healthBefore = (int) $sick->health;

        $miracle = app(RecordMiracleClaim::class)->execute(
            $world,
            MiracleCategory::HEALING,
            'pilgrimage',
            $date,
            1,
            $saint,
            null,
            $sick,
            $sick,
            'territory',
            3,
            [$physician->id]
        );
        $this->assertSame(MiracleCausation::UNKNOWN, $miracle->causation);
        $this->assertSame($healthBefore, (int) $sick->fresh()->health);

        app(InterpretMiracle::class)->execute($world, $miracle, $physician, MiracleReading::NATURAL, $date, 'fever broke');
        app(InterpretMiracle::class)->execute($world, $miracle, $sick, MiracleReading::DIVINE, $date, 'the saint healed me');
        $this->assertSame(MiracleStatus::DISPUTED, $miracle->fresh()->status);

        $recognized = app(RecognizeMiracle::class)->execute($world, $miracle->fresh(), $bishop, $date);
        $this->assertSame(MiracleStatus::RECOGNIZED, $recognized->status);
        $this->assertSame(MiracleCausation::UNKNOWN, $recognized->causation);
        $this->assertSame($healthBefore, (int) $sick->fresh()->health);
        $readings = collect($recognized->interpretations)->pluck('reading')->all();
        $this->assertContains(MiracleReading::NATURAL, $readings);
        $this->assertContains(MiracleReading::DIVINE, $readings);
        $this->assertTrue($recognized->interpretations->contains(fn ($row) => $row->is_official));
    }
}
