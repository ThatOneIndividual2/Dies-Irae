<?php

namespace App\Http\Controllers;

use App\Actions\Campaign\ResolveGameEvent;
use App\Actions\Army\MoveArmy;
use App\Actions\Army\RaiseArmy;
use App\Actions\Army\ResolveBattle;
use App\Actions\Time\AdvanceWorldCalendar;
use App\Domain\Campaign\Fog\ApocalypseFog;
use App\Domain\Playable\CurrentPlay;
use App\Models\Army;
use App\Models\CampaignGoalProgress;
use App\Models\CampaignState;
use App\Models\CorruptionState;
use App\Models\Cult;
use App\Models\GameEvent;
use App\Models\HeresyPresence;
use App\Models\Monastery;
use App\Models\NamedDemon;
use App\Models\Papacy;
use App\Models\PlagueWave;
use App\Models\Realm;
use App\Models\See;
use App\Models\SeeTerritory;
use App\Models\SpiritualOffice;
use App\Models\Territory;
use App\Models\Title;
use App\Models\TitleOwnership;
use App\Models\VassalRelationship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use RuntimeException;

class PlayController extends Controller
{
    private function play(): CurrentPlay
    {
        return CurrentPlay::require(Auth::user());
    }

    public function dashboard()
    {
        $play = $this->play();
        $pending = GameEvent::query()
            ->where('world_id', $play->world->id)
            ->where('status', 'awaiting_decision')
            ->where(function ($q) {
                $q->whereNull('visibility')->orWhereIn('visibility', ['player', 'observer']);
            })
            ->orderBy('due_on')
            ->get();
        $apocalypse = $play->world->apocalypseState;
        $church = $play->ruler->churchRelation;
        $fog = app(ApocalypseFog::class);
        $ageLine = $fog->dashboardLine($play->world);
        $realmName = $this->playerRealm($play)?->name ?? $play->world->name;
        $goals = CampaignGoalProgress::query()
            ->where('world_id', $play->world->id)
            ->where('character_id', $play->ruler->id)
            ->get();

        return view('play.dashboard', compact('play', 'pending', 'apocalypse', 'church', 'ageLine', 'realmName', 'goals'));
    }

    public function character()
    {
        $play = $this->play();
        $play->ruler->load(['dynasty', 'faith', 'residence', 'clergyStatus']);
        $titles = TitleOwnership::query()->where('holder_character_id', $play->ruler->id)->where('is_current', true)->with('title')->get();

        return view('play.character', compact('play', 'titles'));
    }

    public function dynasty()
    {
        $play = $this->play();
        $dynasty = $play->ruler->dynasty()->with(['characters.residence', 'houses'])->first();

        return view('play.dynasty', compact('play', 'dynasty'));
    }

    public function titles()
    {
        $play = $this->play();
        $titles = Title::query()->where('world_id', $play->world->id)->with('currentOwnership.holder', 'capital')->get();

        return view('play.titles', compact('play', 'titles'));
    }

    public function realm()
    {
        $play = $this->play();
        $realm = $this->playerRealm($play);
        $vassals = VassalRelationship::query()
            ->where('liege_character_id', $play->ruler->id)
            ->where('is_current', true)
            ->with(['vassal', 'contract'])
            ->get();

        return view('play.realm', compact('play', 'realm', 'vassals'));
    }

    public function map(Request $request)
    {
        $play = $this->play();
        $mode = $request->query('mode', 'political');
        $territories = Territory::query()
            ->where('world_id', $play->world->id)
            ->with(['plagueState', 'overlay', 'despair', 'cult'])
            ->get();

        return view('play.map', compact('play', 'territories', 'mode'));
    }

    public function settlement(Territory $territory)
    {
        $play = $this->play();
        abort_unless((int) $territory->world_id === (int) $play->world->id, 404);
        $territory->load(['holdings', 'plagueState.wave', 'overlay', 'despair', 'cult.faction', 'monastery', 'armies.owner']);
        $corruption = CorruptionState::query()->where('subject_type', 'territory')->where('subject_id', $territory->id)->first();

        return view('play.settlement', compact('play', 'territory', 'corruption'));
    }

    public function church()
    {
        $play = $this->play();
        $see = See::query()->where('world_id', $play->world->id)->where('key', 'diocese_aix')->with(['seat', 'churchProvince.faith'])->first();
        if (!$see && $play->ruler->residence_territory_id) {
            $seeId = SeeTerritory::query()->where('territory_id', $play->ruler->residence_territory_id)->value('see_id');
            $see = $seeId
                ? See::query()->with(['seat', 'churchProvince.faith'])->find($seeId)
                : See::query()->where('world_id', $play->world->id)->with(['seat', 'churchProvince.faith'])->first();
        }
        $offices = SpiritualOffice::query()->where('world_id', $play->world->id)->with('currentHoldership.holder')->get();
        $papacy = Papacy::query()->where('world_id', $play->world->id)->with('papalOffice.currentHoldership.holder', 'papalSee')->first();
        $monastery = Monastery::query()->where('world_id', $play->world->id)->with('territory')->first();
        $relation = $play->ruler->churchRelation;

        return view('play.church', compact('play', 'see', 'offices', 'papacy', 'monastery', 'relation'));
    }

    public function spiritual()
    {
        $play = $this->play();
        $territories = Territory::query()->where('world_id', $play->world->id)->with(['overlay', 'despair'])->get();
        $corruptions = CorruptionState::query()->where('world_id', $play->world->id)->get();
        $heresy = HeresyPresence::query()->where('world_id', $play->world->id)->with(['heresy', 'territory'])->get();
        $cults = Cult::query()->where('world_id', $play->world->id)->with('territory')->get();
        if (CampaignState::query()->where('world_id', $play->world->id)->exists()) {
            $cults = $cults->filter(fn (Cult $c) => $c->revealed)->values();
        }
        $observer = 'character:'.$play->ruler->id;
        $rumors = NamedDemon::query()
            ->where('world_id', $play->world->id)
            ->with(['knowledge' => function ($q) use ($observer) {
                $q->where('observer_key', $observer);
            }, 'objectives'])
            ->get()
            ->map(function (NamedDemon $demon) use ($observer) {
                $known = $demon->knowledge->pluck('facet')->all();
                $trueName = in_array('true_identity', $known, true);

                return [
                    'shown' => $trueName ? $demon->true_name : ($demon->public_alias ?: 'a pressure without a name'),
                    'true_known' => $trueName,
                    'rank' => in_array('rank', $known, true) ? $demon->hierarchy : null,
                    'motives' => in_array('motives', $known, true)
                        ? $demon->objectives->pluck('aim')->implode('; ')
                        : null,
                    'location' => in_array('location', $known, true) && $demon->territory_id
                        ? (string) $demon->territory_id
                        : null,
                    'wounds' => in_array('vulnerabilities', $known, true) ? ($demon->vulnerabilities ?? []) : [],
                    'signs' => $demon->known_manifestations ?? [],
                ];
            });

        return view('play.spiritual', compact('play', 'territories', 'corruptions', 'heresy', 'cults', 'rumors'));
    }

    public function plague()
    {
        $play = $this->play();
        $waves = PlagueWave::query()->where('world_id', $play->world->id)->with('territoryStates.territory')->get();

        return view('play.plague', compact('play', 'waves'));
    }

    public function army()
    {
        $play = $this->play();
        $armies = Army::query()->where('world_id', $play->world->id)->with(['territory.neighbors', 'owner', 'commander'])->get();
        $home = $play->ruler->residence
            ?? Territory::query()->where('world_id', $play->world->id)->where('key', 'salon')->first();
        $battles = $play->world->id
            ? \App\Models\Battle::query()->where('world_id', $play->world->id)->with('territory')->latest()->limit(10)->get()
            : collect();

        return view('play.army', compact('play', 'armies', 'home', 'battles'));
    }

    public function apocalypse()
    {
        $play = $this->play();
        $fog = app(ApocalypseFog::class);
        $isCampaign = $fog->isCampaignWorld($play->world);
        $view = $isCampaign ? $fog->playerView($play->world, false) : null;
        $current = $play->world->apocalypseState;
        $history = $play->world->apocalypseChronicle()->orderBy('id')->get();

        return view('play.apocalypse', compact('play', 'current', 'history', 'isCampaign', 'view'));
    }

    public function events()
    {
        $play = $this->play();
        $events = GameEvent::query()
            ->where('world_id', $play->world->id)
            ->where(function ($q) {
                $q->whereNull('visibility')->orWhereIn('visibility', ['player', 'observer']);
            })
            ->where(function ($q) use ($play) {
                $q->whereNull('audience_character_id')
                    ->orWhere('audience_character_id', $play->ruler->id);
            })
            ->orderBy('due_on')
            ->get();

        return view('play.events', compact('play', 'events'));
    }

    public function advance(AdvanceWorldCalendar $advance)
    {
        $play = $this->play();
        $advance->execute($play->world, 1);

        return redirect()->route('dashboard')->with('status', 'The calendar advanced one day.');
    }

    public function resolveEvent(Request $request, GameEvent $event, ResolveGameEvent $resolver)
    {
        $play = $this->play();
        abort_unless((int) $event->world_id === (int) $play->world->id, 404);
        abort_unless(
            $event->audience_character_id === null
                || (int) $event->audience_character_id === (int) $play->ruler->id,
            403
        );
        $option = (string) $request->input('option');

        $label = $event->optionLabel($option) ?? $option;

        try {
            $resolver->execute($event, $option);
        } catch (InvalidArgumentException|RuntimeException $e) {
            return redirect()->route('events')->with('error', $e->getMessage());
        }

        return redirect()->route('events')->with('status', 'Decision recorded: '.$label);
    }

    public function raiseArmy(Request $request, RaiseArmy $raise)
    {
        $play = $this->play();
        $territory = $play->ruler->residence
            ?? Territory::query()->where('world_id', $play->world->id)->where('key', 'salon')->firstOrFail();
        $strength = max(1, (int) $request->input('strength', 80));
        $name = $territory->key === 'salon' ? 'Levy of Salon' : 'Levy of '.$territory->name;
        $raise->execute($play->ruler, $territory, $strength, $name);

        return redirect()->route('army')->with('status', "Raised {$strength} men at {$territory->name}.");
    }

    public function moveArmy(Request $request, Army $army, MoveArmy $move)
    {
        $play = $this->play();
        abort_unless((int) $army->world_id === (int) $play->world->id, 404);
        abort_unless((int) $army->owner_character_id === (int) $play->ruler->id, 403);
        $destination = Territory::query()->where('world_id', $play->world->id)->findOrFail($request->input('territory_id'));
        $move->execute($army, $destination);

        return redirect()->route('army')->with('status', 'The host marched to '.$destination->name.'.');
    }

    public function fight(Request $request, Army $army, ResolveBattle $battle)
    {
        $play = $this->play();
        abort_unless((int) $army->world_id === (int) $play->world->id, 404);
        abort_unless((int) $army->owner_character_id === (int) $play->ruler->id, 403);
        $enemy = Army::query()
            ->where('world_id', $play->world->id)
            ->where('territory_id', $army->territory_id)
            ->where('id', '!=', $army->id)
            ->where('is_active', true)
            ->findOrFail($request->input('enemy_army_id'));
        $kind = $enemy->kind === 'demonic' ? 'supernatural' : 'human';
        $result = $battle->execute($army, $enemy, $kind);

        return redirect()->route('army')->with('status', 'Battle fought. Winner: '.$result->winner);
    }

    private function playerRealm($play): ?Realm
    {
        $owned = TitleOwnership::query()
            ->where('holder_character_id', $play->ruler->id)
            ->where('is_current', true)
            ->pluck('title_id');

        return Realm::query()
            ->where('world_id', $play->world->id)
            ->where('top_liege_character_id', $play->ruler->id)
            ->with('primaryTitle', 'topLiege')
            ->first()
            ?? Realm::query()
                ->where('world_id', $play->world->id)
                ->whereIn('primary_title_id', $owned)
                ->with('primaryTitle', 'topLiege')
                ->first()
            ?? Realm::query()->where('world_id', $play->world->id)->with('primaryTitle', 'topLiege')->first();
    }
}
