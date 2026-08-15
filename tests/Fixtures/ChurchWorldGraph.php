<?php

namespace Tests\Fixtures;

use App\Actions\Church\CreatePapacy;
use App\Actions\Church\CreateSee;
use App\Actions\Church\CreateSpiritualOffice;
use App\Actions\Church\SetClergyStatus;
use App\Actions\Titles\CreateTitle;
use App\Actions\Titles\GrantTitle;
use App\Actions\World\CreateWorld;
use App\Domain\Enums\AcquisitionType;
use App\Domain\Enums\AppointmentMode;
use App\Domain\Enums\ClergyGrade;
use App\Domain\Enums\ReligiousState;
use App\Domain\Enums\SeeKind;
use App\Domain\Enums\SpiritualOfficeRank;
use App\Domain\Church\SpiritualOfficeHoldershipMutator;
use App\Domain\Enums\ClergyLegitimacyStatus;
use App\Domain\Enums\OfficeAcquisitionType;
use App\Domain\Enums\PapacyStatus;
use App\Domain\Enums\TitleRank;
use App\Models\Character;
use App\Models\ChurchProvince;
use App\Models\Dynasty;
use App\Models\Faith;
use App\Models\Holding;
use App\Models\HolyOrder;
use App\Models\Monastery;
use App\Models\Papacy;
use App\Models\Region;
use App\Models\ReligiousOrder;
use App\Models\See;
use App\Models\SeeTerritory;
use App\Models\SpiritualOffice;
use App\Models\Territory;
use App\Models\Title;
use App\Models\VassalRelationship;
use App\Models\World;
use Carbon\Carbon;

final class ChurchWorldGraph
{
    public World $world;
    public Faith $faith;
    public ChurchProvince $province;
    public Region $region;
    public Territory $rome;
    public Territory $reims;
    public Territory $paris;
    public Territory $parishLand;
    public Holding $castle;
    public Holding $abbeyHolding;
    public Dynasty $capet;
    public Character $pope;
    public Character $king;
    public Character $princeBishop;
    public Character $archbishop;
    public Character $bishop;
    public Character $priest;
    public Character $deacon;
    public Character $abbot;
    public Character $monk;
    public Character $layCount;
    public Character $antipope;
    public See $papalSee;
    public See $archdiocese;
    public See $diocese;
    public See $parish;
    public SpiritualOffice $papalOffice;
    public SpiritualOffice $archbishopOffice;
    public SpiritualOffice $bishopOffice;
    public SpiritualOffice $priestOffice;
    public SpiritualOffice $deaconOffice;
    public SpiritualOffice $abbotOffice;
    public SpiritualOffice $cardinalOffice;
    public Papacy $papacy;
    public Title $kingdom;
    public Title $princeBishopCounty;
    public Title $county;
    public ReligiousOrder $benedictines;
    public Monastery $monastery;
    public HolyOrder $templars;
    public Carbon $date;

    public static function seed(): self
    {
        $g = new self();
        $g->date = Carbon::parse('1348-06-24');
        $g->world = app(CreateWorld::class)->execute('Lys', ['slug' => 'lys-church-'.uniqid()]);

        $g->faith = Faith::query()->create([
            'world_id' => $g->world->id,
            'key' => 'latin_christianity',
            'name' => 'Latin Christianity',
            'rite' => 'roman',
            'is_primary' => true,
        ]);

        $g->region = Region::query()->create([
            'world_id' => $g->world->id,
            'key' => 'fr',
            'name' => 'Francia',
            'map_key' => 'FR',
        ]);

        $g->rome = $g->territory('rome', 'Rome');
        $g->reims = $g->territory('reims', 'Reims');
        $g->paris = $g->territory('paris', 'Paris');
        $g->parishLand = $g->territory('parish-land', 'Saint-Denis parish');

        $g->castle = Holding::query()->create([
            'world_id' => $g->world->id,
            'territory_id' => $g->paris->id,
            'key' => 'paris-castle',
            'name' => 'Paris Castle',
            'holding_type' => 'castle',
        ]);

        $g->abbeyHolding = Holding::query()->create([
            'world_id' => $g->world->id,
            'territory_id' => $g->parishLand->id,
            'key' => 'st-denis-abbey',
            'name' => 'Saint-Denis Abbey',
            'holding_type' => 'monastery',
        ]);

        $g->capet = Dynasty::query()->create([
            'world_id' => $g->world->id,
            'key' => 'capet',
            'name' => 'Capet',
        ]);

        $g->pope = $g->person('pope-clement', 'Clement', null);
        $g->king = $g->person('king-philip', 'Philip', $g->capet->id);
        $g->princeBishop = $g->person('prince-bishop-louis', 'Louis', $g->capet->id);
        $g->archbishop = $g->person('archbishop-jean', 'Jean', null);
        $g->bishop = $g->person('bishop-pierre', 'Pierre', null);
        $g->priest = $g->person('priest-andre', 'Andre', null);
        $g->deacon = $g->person('deacon-marc', 'Marc', null);
        $g->abbot = $g->person('abbot-benoit', 'Benoit', null);
        $g->monk = $g->person('monk-thomas', 'Thomas', null);
        $g->layCount = $g->person('count-robert', 'Robert', $g->capet->id);
        $g->antipope = $g->person('antipope-nicholas', 'Nicholas', null);

        $g->province = ChurchProvince::query()->create([
            'world_id' => $g->world->id,
            'faith_id' => $g->faith->id,
            'key' => 'reims-province',
            'name' => 'Province of Reims',
        ]);

        $createSee = app(CreateSee::class);
        $g->papalSee = $createSee->execute($g->world, 'holy-see', 'Holy See', SeeKind::PAPAL_SEE, [
            'territory_id' => $g->rome->id,
        ]);
        $g->archdiocese = $createSee->execute($g->world, 'reims', 'Archdiocese of Reims', SeeKind::ARCHDIOCESE, [
            'church_province_id' => $g->province->id,
            'parent_see_id' => $g->papalSee->id,
            'territory_id' => $g->reims->id,
        ]);
        $g->diocese = $createSee->execute($g->world, 'paris', 'Diocese of Paris', SeeKind::DIOCESE, [
            'church_province_id' => $g->province->id,
            'parent_see_id' => $g->archdiocese->id,
            'territory_id' => $g->paris->id,
        ]);
        $g->parish = $createSee->execute($g->world, 'st-denis', 'Parish of Saint-Denis', SeeKind::PARISH, [
            'church_province_id' => $g->province->id,
            'parent_see_id' => $g->diocese->id,
            'territory_id' => $g->parishLand->id,
        ]);

        $g->province->metropolitan_see_id = $g->archdiocese->id;
        $g->province->save();

        foreach ([$g->papalSee, $g->archdiocese, $g->diocese, $g->parish] as $see) {
            if ($see->territory_id) {
                SeeTerritory::query()->create([
                    'world_id' => $g->world->id,
                    'see_id' => $see->id,
                    'territory_id' => $see->territory_id,
                ]);
            }
        }

        $createOffice = app(CreateSpiritualOffice::class);
        $g->papalOffice = $createOffice->execute($g->world, 'pope', 'Bishop of Rome', SpiritualOfficeRank::POPE, [
            'see_id' => $g->papalSee->id,
            'as_ordinary' => true,
            'is_papal_apex' => true,
            'appointment_mode' => AppointmentMode::CONCLAVE,
        ]);
        $g->cardinalOffice = $createOffice->execute($g->world, 'cardinal-reims', 'Cardinal of Reims', SpiritualOfficeRank::CARDINAL, [
            'see_id' => $g->archdiocese->id,
        ]);
        $g->archbishopOffice = $createOffice->execute($g->world, 'archbishop-reims', 'Archbishop of Reims', SpiritualOfficeRank::ARCHBISHOP, [
            'see_id' => $g->archdiocese->id,
            'as_ordinary' => true,
        ]);
        $g->bishopOffice = $createOffice->execute($g->world, 'bishop-paris', 'Bishop of Paris', SpiritualOfficeRank::BISHOP, [
            'see_id' => $g->diocese->id,
            'as_ordinary' => true,
        ]);
        $g->priestOffice = $createOffice->execute($g->world, 'priest-st-denis', 'Parish Priest of Saint-Denis', SpiritualOfficeRank::PRIEST, [
            'see_id' => $g->parish->id,
            'as_ordinary' => true,
        ]);
        $g->deaconOffice = $createOffice->execute($g->world, 'deacon-st-denis', 'Deacon of Saint-Denis', SpiritualOfficeRank::DEACON, [
            'see_id' => $g->parish->id,
        ]);

        $g->papacy = app(CreatePapacy::class)->execute($g->world, $g->faith, $g->papalSee->fresh(), $g->papalOffice->fresh());

        $seat = app(SpiritualOfficeHoldershipMutator::class);
        $seat->appoint($g->papalOffice->fresh(), $g->pope, $g->date, OfficeAcquisitionType::ELECTION, null, ClergyLegitimacyStatus::RECOGNIZED);
        $g->papacy->status = PapacyStatus::OCCUPIED;
        $g->papacy->save();
        $seat->appoint($g->archbishopOffice, $g->archbishop, $g->date, OfficeAcquisitionType::PAPAL_PROVISION, $g->pope);
        $seat->appoint($g->bishopOffice, $g->bishop, $g->date, OfficeAcquisitionType::PAPAL_PROVISION, $g->pope);
        $seat->appoint($g->priestOffice, $g->priest, $g->date, OfficeAcquisitionType::APPOINTMENT, $g->bishop);
        $seat->appoint($g->deaconOffice, $g->deacon, $g->date, OfficeAcquisitionType::APPOINTMENT, $g->bishop);

        $g->benedictines = ReligiousOrder::query()->create([
            'world_id' => $g->world->id,
            'faith_id' => $g->faith->id,
            'key' => 'benedictine',
            'name' => 'Order of Saint Benedict',
            'kind' => 'monastic',
            'rule_key' => 'benedictine',
            'sanction_status' => 'sanctioned',
            'sanctioned_date' => '529-01-01',
        ]);

        $g->monastery = Monastery::query()->create([
            'world_id' => $g->world->id,
            'holding_id' => $g->abbeyHolding->id,
            'faith_id' => $g->faith->id,
            'religious_order_id' => $g->benedictines->id,
            'key' => 'st-denis',
            'name' => 'Abbey of Saint-Denis',
            'rule' => 'benedictine',
            'is_exempt' => true,
            'is_active' => true,
            'religious_population' => 40,
        ]);

        $g->abbotOffice = $createOffice->execute($g->world, 'abbot-st-denis', 'Abbot of Saint-Denis', SpiritualOfficeRank::ABBOT, [
            'monastery_id' => $g->monastery->id,
            'religious_order_id' => $g->benedictines->id,
            'appointment_mode' => AppointmentMode::ABBATIAL_ELECTION,
        ]);
        $g->monastery->abbot_office_id = $g->abbotOffice->id;
        $g->monastery->save();
        $seat->appoint($g->abbotOffice, $g->abbot, $g->date, OfficeAcquisitionType::ELECTION);

        $g->templars = HolyOrder::query()->create([
            'world_id' => $g->world->id,
            'faith_id' => $g->faith->id,
            'religious_order_id' => $g->benedictines->id,
            'key' => 'temple',
            'name' => 'Poor Fellow-Soldiers of Christ',
            'sanction_status' => 'unsanctioned',
        ]);

        $setStatus = app(SetClergyStatus::class);
        $setStatus->execute($g->pope, 'bishop', $g->date, [
            'orders_grade' => ClergyGrade::BISHOP,
            'religious_state' => ReligiousState::SECULAR_CLERIC,
        ]);
        $setStatus->execute($g->archbishop, 'bishop', $g->date, [
            'orders_grade' => ClergyGrade::BISHOP,
            'religious_state' => ReligiousState::SECULAR_CLERIC,
        ]);
        $setStatus->execute($g->bishop, 'bishop', $g->date, [
            'orders_grade' => ClergyGrade::BISHOP,
            'religious_state' => ReligiousState::SECULAR_CLERIC,
        ]);
        $setStatus->execute($g->princeBishop, 'bishop', $g->date, [
            'orders_grade' => ClergyGrade::BISHOP,
            'religious_state' => ReligiousState::SECULAR_CLERIC,
        ]);
        $setStatus->execute($g->priest, 'priest', $g->date, [
            'orders_grade' => ClergyGrade::PRIEST,
            'religious_state' => ReligiousState::SECULAR_CLERIC,
        ]);
        $setStatus->execute($g->deacon, 'deacon', $g->date, [
            'orders_grade' => ClergyGrade::DEACON,
            'religious_state' => ReligiousState::SECULAR_CLERIC,
        ]);
        $setStatus->execute($g->abbot, 'abbot', $g->date, [
            'orders_grade' => ClergyGrade::PRIEST,
            'religious_state' => ReligiousState::REGULAR,
            'religious_order_id' => $g->benedictines->id,
            'monastery_id' => $g->monastery->id,
        ]);
        $setStatus->execute($g->monk, 'monk', $g->date, [
            'orders_grade' => ClergyGrade::TONSURE,
            'religious_state' => ReligiousState::REGULAR,
            'religious_order_id' => $g->benedictines->id,
            'monastery_id' => $g->monastery->id,
        ]);

        $createTitle = app(CreateTitle::class);
        $g->kingdom = $createTitle->execute($g->world, 'Kingdom of France', TitleRank::KINGDOM, ['key' => 'france']);
        $g->princeBishopCounty = $createTitle->execute($g->world, 'County of Liege', TitleRank::COUNTY, [
            'key' => 'liege',
            'parent_title_id' => $g->kingdom->id,
            'capital_territory_id' => $g->paris->id,
        ]);
        $g->county = $createTitle->execute($g->world, 'County of Amiens', TitleRank::COUNTY, [
            'key' => 'amiens',
            'parent_title_id' => $g->kingdom->id,
        ]);

        $grant = app(GrantTitle::class);
        $grant->execute($g->kingdom, $g->king, $g->date, null, AcquisitionType::CREATION);
        $grant->execute($g->princeBishopCounty, $g->princeBishop, $g->date, $g->king, AcquisitionType::GRANT);
        $grant->execute($g->county, $g->layCount, $g->date, $g->king, AcquisitionType::GRANT);

        $g->castle->owner_character_id = $g->king->id;
        $g->castle->save();

        VassalRelationship::query()->create([
            'world_id' => $g->world->id,
            'liege_character_id' => $g->king->id,
            'vassal_character_id' => $g->princeBishop->id,
            'primary_title_id' => $g->princeBishopCounty->id,
            'started_date' => $g->date->toDateString(),
            'is_current' => true,
        ]);
        VassalRelationship::query()->create([
            'world_id' => $g->world->id,
            'liege_character_id' => $g->king->id,
            'vassal_character_id' => $g->layCount->id,
            'primary_title_id' => $g->county->id,
            'started_date' => $g->date->toDateString(),
            'is_current' => true,
        ]);

        return $g;
    }

    private function territory(string $key, string $name): Territory
    {
        return Territory::query()->create([
            'world_id' => $this->world->id,
            'region_id' => $this->region->id,
            'key' => $key,
            'name' => $name,
            'territory_type' => 'county',
        ]);
    }

    private function person(string $key, string $firstName, ?int $dynastyId): Character
    {
        return Character::query()->create([
            'world_id' => $this->world->id,
            'key' => $key,
            'dynasty_id' => $dynastyId,
            'first_name' => $firstName,
            'sex' => 'male',
            'birth_date' => '1310-01-01',
            'is_alive' => true,
            'faith_id' => $this->faith->id,
            'legitimacy_status' => 'legitimate',
        ]);
    }
}
