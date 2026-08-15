<?php

namespace Tests\Feature\Spiritual;

use App\Actions\Spiritual\ApplyCorruption;
use App\Actions\Spiritual\CleanseCorruption;
use App\Actions\Spiritual\CompletePenance;
use App\Actions\Spiritual\ConferSacrament;
use App\Actions\Spiritual\RecordCanonicalCensure;
use App\Actions\Spiritual\RecordSpiritualAct;
use App\Actions\Spiritual\RegisterTemptation;
use App\Actions\Spiritual\SetDemonicInfluence;
use App\Domain\Enums\CapitalVice;
use App\Domain\Enums\CanonicalCensure;
use App\Domain\Enums\CorruptionKind;
use App\Domain\Enums\CorruptionSubjectType;
use App\Domain\Enums\DemonicInfluenceStage;
use App\Domain\Enums\EucharistStanding;
use App\Domain\Enums\HolyOrdersGrade;
use App\Domain\Enums\SacramentType;
use App\Domain\Enums\SacramentValidity;
use App\Domain\Enums\SpiritualActType;
use App\Domain\Enums\SpiritualObserverContext;
use App\Domain\Spiritual\Ports\ClericalAuthorityPort;
use App\Domain\Spiritual\Ports\InMemoryClericalAuthority;
use App\Domain\Spiritual\SpiritualInspectionService;
use App\Domain\Spiritual\SpiritualVisibilityService;
use App\Models\Character;
use App\Models\CharacterCanonicalState;
use App\Models\CharacterSpiritualState;
use App\Models\Penance;
use App\Models\World;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;
use Tests\Feature\LaravelTestCase;

class SpiritualEngineTest extends LaravelTestCase
{
    use DatabaseTransactions;

    private function world(): World
    {
        $date = '1348-01-01';
        $suffix = substr(bin2hex(random_bytes(4)), 0, 8);
        $row = [
            'slug' => 'spirit-'.$suffix,
            'name' => 'Spiritual Test '.$suffix,
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

    private function person(World $world): Character
    {
        return Character::query()->create([
            'world_id' => $world->id,
            'key' => 'c-'.substr(bin2hex(random_bytes(6)), 0, 12),
            'first_name' => 'Test',
            'sex' => 'male',
            'birth_date' => '1320-01-01',
            'is_alive' => true,
            'legitimacy_status' => 'legitimate',
        ]);
    }

    private function clergy(): InMemoryClericalAuthority
    {
        $port = app(ClericalAuthorityPort::class);
        $this->assertInstanceOf(InMemoryClericalAuthority::class, $port);

        return $port;
    }

    public function test_murder_moves_several_axes_and_stays_private(): void
    {
        $world = $this->world();
        $killer = $this->person($world);
        $stranger = $this->person($world);
        $date = Carbon::parse('1348-03-01');

        $record = app(RecordSpiritualAct::class)->execute($world, $killer, SpiritualActType::MURDER, $date, false);

        $state = CharacterSpiritualState::query()->where('character_id', $killer->id)->where('is_current', true)->first();
        $canonical = CharacterCanonicalState::query()->where('character_id', $killer->id)->where('is_current', true)->first();

        $this->assertSame(38, $state->wrath);
        $this->assertSame(24, $state->charity);
        $this->assertSame(8, $state->despair);
        $this->assertTrue($canonical->grave_unconfessed);
        $this->assertSame(EucharistStanding::UNBAPTIZED, $canonical->eucharist_standing);
        $this->assertSame(SpiritualActType::MURDER, $record->act_type);

        $view = app(SpiritualVisibilityService::class)->viewFor($stranger, $killer, SpiritualObserverContext::PUBLIC);
        $this->assertArrayNotHasKey('wrath', $view->toArray()['public']);
        $this->assertSame([], $view->private);
        $this->assertFalse($view->hasExactScore(CapitalVice::WRATH));
    }

    public function test_public_murder_creates_scandal_without_publishing_scores(): void
    {
        $world = $this->world();
        $killer = $this->person($world);
        app(RecordSpiritualAct::class)->execute($world, $killer, SpiritualActType::MURDER, Carbon::parse('1348-03-02'), true);

        $view = app(SpiritualVisibilityService::class)->viewFor($this->person($world), $killer, SpiritualObserverContext::PUBLIC);
        $this->assertNotEmpty($view->public['scandals']);
        $this->assertSame('murder', $view->public['scandals'][0]['facet']);
        $this->assertArrayNotHasKey('wrath', $view->public);
    }

    public function test_baptism_and_confession_have_consequences(): void
    {
        $world = $this->world();
        $penitent = $this->person($world);
        $priest = $this->person($world);
        $this->clergy()->grant($priest, HolyOrdersGrade::PRIEST);
        $date = Carbon::parse('1348-04-01');

        $baptism = app(ConferSacrament::class)->execute($world, SacramentType::BAPTISM, $penitent, $date, $priest);
        $this->assertSame(SacramentValidity::VALID, $baptism->validity);
        $this->assertTrue($baptism->consequences_applied);

        app(RecordSpiritualAct::class)->execute($world, $penitent, SpiritualActType::MURDER, $date->copy()->addDay(), false);

        $confession = app(ConferSacrament::class)->execute(
            $world,
            SacramentType::CONFESSION,
            $penitent,
            $date->copy()->addDays(2),
            $priest
        );
        $this->assertSame(SacramentValidity::VALID, $confession->validity);

        $canonical = CharacterCanonicalState::query()->where('character_id', $penitent->id)->where('is_current', true)->first();
        $this->assertTrue($canonical->is_baptized);
        $this->assertFalse($canonical->grave_unconfessed);
        $this->assertSame(EucharistStanding::IN_COMMUNION, $canonical->eucharist_standing);
        $this->assertSame(1, Penance::query()->where('character_id', $penitent->id)->count());

        $sealedView = app(SpiritualVisibilityService::class)->viewFor($priest, $penitent, SpiritualObserverContext::CONFESSOR);
        $this->assertNotEmpty($sealedView->sealed);

        $this->expectException(\DomainException::class);
        app(SpiritualVisibilityService::class)->viewFor($priest, $penitent, SpiritualObserverContext::POLITICAL);
    }

    public function test_eucharist_is_invalid_without_baptism(): void
    {
        $world = $this->world();
        $subject = $this->person($world);
        $priest = $this->person($world);
        $this->clergy()->grant($priest, HolyOrdersGrade::PRIEST);

        $record = app(ConferSacrament::class)->execute(
            $world,
            SacramentType::EUCHARIST,
            $subject,
            Carbon::parse('1348-05-01'),
            $priest
        );

        $this->assertSame(SacramentValidity::INVALID, $record->validity);
        $this->assertFalse($record->consequences_applied);
        $this->assertContains('unbaptized', $record->reasons);
    }

    public function test_matrimony_bond_blocks_a_second_valid_marriage(): void
    {
        $world = $this->world();
        $a = $this->person($world);
        $b = $this->person($world);
        $c = $this->person($world);
        $priest = $this->person($world);
        $bishop = $this->person($world);
        $this->clergy()->grant($priest, HolyOrdersGrade::PRIEST);
        $this->clergy()->grant($bishop, HolyOrdersGrade::BISHOP);
        $date = Carbon::parse('1348-06-01');

        app(ConferSacrament::class)->execute($world, SacramentType::BAPTISM, $a, $date, $priest);
        app(ConferSacrament::class)->execute($world, SacramentType::BAPTISM, $b, $date, $priest);
        app(ConferSacrament::class)->execute($world, SacramentType::BAPTISM, $c, $date, $priest);

        $first = app(ConferSacrament::class)->execute($world, SacramentType::MATRIMONY, $a, $date, $priest, $b);
        $this->assertSame(SacramentValidity::VALID, $first->validity);

        $second = app(ConferSacrament::class)->execute($world, SacramentType::MATRIMONY, $a, $date->copy()->addYear(), $priest, $c);
        $this->assertSame(SacramentValidity::INVALID, $second->validity);
        $this->assertContains('existing_bond', $second->reasons);

        $aCanon = CharacterCanonicalState::query()->where('character_id', $a->id)->where('is_current', true)->first();
        $bCanon = CharacterCanonicalState::query()->where('character_id', $b->id)->where('is_current', true)->first();
        $this->assertSame($b->id, $aCanon->matrimonial_bond_character_id);
        $this->assertSame($a->id, $bCanon->matrimonial_bond_character_id);
    }

    public function test_holy_orders_are_not_a_secular_title(): void
    {
        $world = $this->world();
        $subject = $this->person($world);
        $bishop = $this->person($world);
        $this->clergy()->grant($bishop, HolyOrdersGrade::BISHOP);
        $date = Carbon::parse('1348-07-01');

        app(ConferSacrament::class)->execute($world, SacramentType::BAPTISM, $subject, $date, $bishop);
        $orders = app(ConferSacrament::class)->execute(
            $world,
            SacramentType::HOLY_ORDERS,
            $subject,
            $date,
            $bishop,
            null,
            null,
            null,
            HolyOrdersGrade::PRIEST
        );

        $this->assertSame(SacramentValidity::VALID, $orders->validity);
        $canonical = CharacterCanonicalState::query()->where('character_id', $subject->id)->where('is_current', true)->first();
        $this->assertSame(HolyOrdersGrade::PRIEST, $canonical->holy_orders_grade);
        $this->assertFalse(\Illuminate\Support\Facades\Schema::hasColumn('characters', 'title_rank'));
        $this->assertSame(0, \Illuminate\Support\Facades\DB::table('sacrament_records')->where('sacrament_type', 'bishopric')->count());
    }

    public function test_excommunication_is_public_and_bars_communion(): void
    {
        $world = $this->world();
        $subject = $this->person($world);
        $priest = $this->person($world);
        $this->clergy()->grant($priest, HolyOrdersGrade::PRIEST);
        $date = Carbon::parse('1348-08-01');
        app(ConferSacrament::class)->execute($world, SacramentType::BAPTISM, $subject, $date, $priest);

        app(RecordCanonicalCensure::class)->execute($world, $subject, CanonicalCensure::EXCOMMUNICATION, 'church_act', 1);

        $canonical = CharacterCanonicalState::query()->where('character_id', $subject->id)->where('is_current', true)->first();
        $this->assertSame(CanonicalCensure::EXCOMMUNICATION, $canonical->censure);
        $this->assertSame(EucharistStanding::BARRED_BY_CENSURE, $canonical->eucharist_standing);

        $public = app(SpiritualVisibilityService::class)->viewFor($this->person($world), $subject, SpiritualObserverContext::PUBLIC);
        $this->assertTrue($public->public['excommunication']);
        $this->assertArrayNotHasKey('faith', $public->public);

        $eucharist = app(ConferSacrament::class)->execute($world, SacramentType::EUCHARIST, $subject, $date, $priest);
        $this->assertSame(SacramentValidity::INVALID, $eucharist->validity);
    }

    public function test_clergy_see_canonical_standing_not_interior_vices(): void
    {
        $world = $this->world();
        $subject = $this->person($world);
        $priest = $this->person($world);
        $this->clergy()->grant($priest, HolyOrdersGrade::PRIEST);
        $date = Carbon::parse('1348-09-01');
        app(ConferSacrament::class)->execute($world, SacramentType::BAPTISM, $subject, $date, $priest);
        app(RecordSpiritualAct::class)->execute($world, $subject, SpiritualActType::ADULTERY, $date, false);

        $view = app(SpiritualVisibilityService::class)->viewFor($priest, $subject, SpiritualObserverContext::CLERGY);
        $this->assertSame(EucharistStanding::BARRED_BY_SIN, $view->clergy['eucharist_standing']);
        $this->assertArrayNotHasKey('lust', $view->clergy);
        $this->assertSame([], $view->private);
    }

    public function test_admin_inspect_sees_everything(): void
    {
        $world = $this->world();
        $subject = $this->person($world);
        app(RecordSpiritualAct::class)->execute($world, $subject, SpiritualActType::BETRAYAL, Carbon::parse('1348-10-01'), false);

        $payload = app(SpiritualInspectionService::class)->inspectCharacter($subject);
        $this->assertArrayHasKey('faith', $payload['view']['private']);
        $this->assertArrayHasKey('spiritual_state', $payload);
        $this->assertArrayHasKey('canonical_state', $payload);
        $this->assertTrue($payload['view']['admin']);
    }

    public function test_army_and_territory_corruption_are_independent(): void
    {
        $world = $this->world();
        $date = Carbon::parse('1348-11-01');

        $army = app(ApplyCorruption::class)->execute($world, CorruptionSubjectType::ARMY, 9, CorruptionKind::INFERNAL_TAINT, 40, $date);
        $land = app(ApplyCorruption::class)->execute($world, CorruptionSubjectType::TERRITORY, 9, CorruptionKind::SPIRITUAL_ROT, 25, $date);
        $abbey = app(ApplyCorruption::class)->execute($world, CorruptionSubjectType::MONASTERY, 9, CorruptionKind::SPIRITUAL_ROT, 40, $date);

        $this->assertSame(40, $army->intensity);
        $this->assertSame(25, $land->intensity);
        $this->assertNotSame($army->id, $land->id);
        $this->assertNotSame($abbey->id, $land->id);

        $inspectArmy = app(SpiritualInspectionService::class)->inspectSubject(CorruptionSubjectType::ARMY, 9);
        $inspectLand = app(SpiritualInspectionService::class)->inspectSubject(CorruptionSubjectType::TERRITORY, 9);
        $inspectAbbey = app(SpiritualInspectionService::class)->inspectSubject(CorruptionSubjectType::MONASTERY, 9);
        $this->assertArrayHasKey('morale', $inspectArmy['modifiers']);
        $this->assertArrayHasKey('veil_thinness', $inspectLand['modifiers']);
        $this->assertArrayHasKey('liturgy_quality', $inspectAbbey['modifiers']);
        $this->assertArrayNotHasKey('morale', $inspectAbbey['modifiers']);
        $this->assertTrue($inspectLand['modifiers']['legal_title_unaffected']);

        app(CleanseCorruption::class)->execute($world, CorruptionSubjectType::ARMY, 9, 40, $date);
        $this->assertNull(app(\App\Domain\Spiritual\CorruptionService::class)->current(CorruptionSubjectType::ARMY, 9));
        $this->assertNotNull(app(\App\Domain\Spiritual\CorruptionService::class)->current(CorruptionSubjectType::TERRITORY, 9));
    }

    public function test_confirmation_and_anointing_change_resistance_and_despair(): void
    {
        $world = $this->world();
        $subject = $this->person($world);
        $bishop = $this->person($world);
        $this->clergy()->grant($bishop, HolyOrdersGrade::BISHOP);
        $date = Carbon::parse('1348-12-01');

        app(ConferSacrament::class)->execute($world, SacramentType::BAPTISM, $subject, $date, $bishop);
        app(RecordSpiritualAct::class)->execute($world, $subject, SpiritualActType::COWARDICE, $date, false);
        $before = CharacterSpiritualState::query()->where('character_id', $subject->id)->where('is_current', true)->first();
        $this->assertGreaterThan(0, $before->despair);

        app(ConferSacrament::class)->execute($world, SacramentType::CONFIRMATION, $subject, $date, $bishop);
        $anointing = app(ConferSacrament::class)->execute($world, SacramentType::ANOINTING, $subject, $date, $bishop);
        $this->assertSame(SacramentValidity::VALID, $anointing->validity);

        $after = CharacterSpiritualState::query()->where('character_id', $subject->id)->where('is_current', true)->first();
        $canonical = CharacterCanonicalState::query()->where('character_id', $subject->id)->where('is_current', true)->first();
        $this->assertTrue($canonical->is_confirmed);
        $this->assertNotNull($canonical->last_anointing_date);
        $this->assertLessThan($before->despair, $after->despair);
    }

    public function test_demonic_influence_whisper_is_private_possession_is_inferable(): void
    {
        $world = $this->world();
        $subject = $this->person($world);
        $stranger = $this->person($world);
        $exorcist = $this->person($world);
        $this->clergy()->grant($exorcist, HolyOrdersGrade::PRIEST, true, true);

        app(SetDemonicInfluence::class)->execute($world, $subject, DemonicInfluenceStage::TEMPTATION, 30, Carbon::parse('1349-01-01'));
        $public = app(SpiritualVisibilityService::class)->viewFor($stranger, $subject, SpiritualObserverContext::PUBLIC);
        $this->assertArrayNotHasKey('possession_signs', $public->inferable);

        app(SetDemonicInfluence::class)->execute($world, $subject, DemonicInfluenceStage::POSSESSION, 70, Carbon::parse('1349-02-01'));
        $public2 = app(SpiritualVisibilityService::class)->viewFor($stranger, $subject, SpiritualObserverContext::PUBLIC);
        $this->assertSame(DemonicInfluenceStage::POSSESSION, $public2->inferable['possession_signs']);

        $exorcistView = app(SpiritualVisibilityService::class)->viewFor($exorcist, $subject, SpiritualObserverContext::EXORCIST);
        $this->assertSame(DemonicInfluenceStage::POSSESSION, $exorcistView->clergy['demonic_stage']);
        $self = app(SpiritualVisibilityService::class)->viewFor($subject, $subject, SpiritualObserverContext::SELF);
        $this->assertSame(70, $self->private['demonic']['intensity']);
    }

    public function test_cross_world_act_is_rejected(): void
    {
        $a = $this->world();
        $b = $this->world();
        $character = $this->person($a);

        $this->expectException(InvalidArgumentException::class);
        app(RecordSpiritualAct::class)->execute($b, $character, SpiritualActType::MERCY, Carbon::parse('1349-03-01'));
    }

    public function test_almsgiving_fasting_pilgrimage_and_mercy_do_not_share_murder_signature(): void
    {
        $world = $this->world();
        $person = $this->person($world);
        $date = Carbon::parse('1349-04-01');
        app(RecordSpiritualAct::class)->execute($world, $person, SpiritualActType::ALMSGIVING, $date);
        app(RecordSpiritualAct::class)->execute($world, $person, SpiritualActType::FASTING, $date);
        app(RecordSpiritualAct::class)->execute($world, $person, SpiritualActType::PILGRIMAGE, $date);
        app(RecordSpiritualAct::class)->execute($world, $person, SpiritualActType::MERCY, $date);

        $state = CharacterSpiritualState::query()->where('character_id', $person->id)->where('is_current', true)->first();
        $this->assertGreaterThan(40, $state->charity);
        $this->assertLessThan(20, $state->greed);
        $this->assertLessThan(20, $state->gluttony);
        $this->assertLessThanOrEqual(20, $state->wrath);
    }

    public function test_temptation_stays_private(): void
    {
        $world = $this->world();
        $person = $this->person($world);
        $stranger = $this->person($world);
        app(RegisterTemptation::class)->execute(
            $world,
            $person,
            CapitalVice::LUST,
            40,
            Carbon::parse('1349-06-01')
        );

        $self = app(SpiritualVisibilityService::class)->viewFor($person, $person, SpiritualObserverContext::SELF);
        $public = app(SpiritualVisibilityService::class)->viewFor($stranger, $person, SpiritualObserverContext::PUBLIC);
        $this->assertNotEmpty($self->private['temptations']);
        $this->assertArrayNotHasKey('temptations', $public->public);
        $this->assertSame([], $public->private);
    }

    public function test_admin_inspect_route_is_local_only(): void
    {
        $world = $this->world();
        $subject = $this->person($world);
        $this->get('/dev/inspect/spiritual/characters/'.$subject->id)->assertOk();
        $this->get('/dev/inspect/spiritual/subjects/'.CorruptionSubjectType::TERRITORY.'/1')->assertOk();
    }

    public function test_completing_penance_moves_repentance(): void
    {
        $world = $this->world();
        $penitent = $this->person($world);
        $priest = $this->person($world);
        $this->clergy()->grant($priest, HolyOrdersGrade::PRIEST);
        $date = Carbon::parse('1349-05-01');
        app(ConferSacrament::class)->execute($world, SacramentType::BAPTISM, $penitent, $date, $priest);
        app(RecordSpiritualAct::class)->execute($world, $penitent, SpiritualActType::SACRILEGE, $date, false);
        app(ConferSacrament::class)->execute($world, SacramentType::CONFESSION, $penitent, $date, $priest);

        $penance = Penance::query()->where('character_id', $penitent->id)->first();
        app(CompletePenance::class)->execute($penance, $date);
        $state = CharacterSpiritualState::query()->where('character_id', $penitent->id)->where('is_current', true)->first();
        $this->assertSame('contrite', $state->repentance);
    }
}
