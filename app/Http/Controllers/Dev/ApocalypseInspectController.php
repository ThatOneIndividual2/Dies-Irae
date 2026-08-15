<?php

namespace App\Http\Controllers\Dev;

use App\Actions\Apocalypse\EnsureApocalypseState;
use App\Actions\Apocalypse\EvaluateApocalypsePhase;
use App\Actions\Apocalypse\ProcessApocalypseTick;
use App\Actions\Apocalypse\RecordApocalypseSignal;
use App\Domain\Apocalypse\ApocalypseCatalog;
use App\Domain\Support\LocalInspectGuard;
use App\Models\ApocalypseChronicleEntry;
use App\Models\ApocalypseMilestoneRecord;
use App\Models\ApocalypseSignal;
use App\Models\TerritorySpiritualWeather;
use App\Models\World;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class ApocalypseInspectController extends Controller
{
    public function index()
    {
        $worlds = World::query()->with('apocalypseState')->orderBy('id')->get();

        return view('dev.inspect.index', compact('worlds'));
    }

    public function show(World $world, EnsureApocalypseState $ensure, ApocalypseCatalog $catalog)
    {
        $state = $ensure->execute($world);
        $milestones = ApocalypseMilestoneRecord::query()
            ->where('world_id', $world->id)
            ->orderBy('id')
            ->get();
        $signals = ApocalypseSignal::query()
            ->where('world_id', $world->id)
            ->orderByDesc('id')
            ->limit(40)
            ->get();
        $chronicle = ApocalypseChronicleEntry::query()
            ->where('world_id', $world->id)
            ->orderByDesc('id')
            ->limit(40)
            ->get();
        $weather = TerritorySpiritualWeather::query()
            ->where('world_id', $world->id)
            ->orderBy('territory_id')
            ->get();
        $signalKeys = array_keys($catalog->signals());
        $phase = $catalog->phase($state->phase_key);

        return view('dev.inspect.apocalypse', compact(
            'world',
            'state',
            'milestones',
            'signals',
            'chronicle',
            'weather',
            'signalKeys',
            'phase'
        ));
    }

    public function signal(Request $request, World $world, RecordApocalypseSignal $record)
    {
        LocalInspectGuard::assertMutable();

        $data = $request->validate([
            'signal_key' => 'required|string',
            'magnitude' => 'required|integer|min:1|max:100',
            'territory_id' => 'nullable|integer',
            'idempotency_key' => 'nullable|string|max:191',
        ]);

        $context = [];
        if (!empty($data['territory_id'])) {
            $context['territory_id'] = (int) $data['territory_id'];
        }
        if (!empty($data['idempotency_key'])) {
            $context['idempotency_key'] = $data['idempotency_key'];
        }

        $record->execute($world, $data['signal_key'], (int) $data['magnitude'], $context);

        return redirect()
            ->route('dev.inspect.apocalypse', $world)
            ->with('status', 'Signal recorded.');
    }

    public function tick(Request $request, World $world, ProcessApocalypseTick $tick)
    {
        LocalInspectGuard::assertMutable();
        $force = $request->boolean('force');
        $tick->execute($world, $force);

        return redirect()
            ->route('dev.inspect.apocalypse', $world)
            ->with('status', 'Tick processed.');
    }

    public function evaluate(World $world, EvaluateApocalypsePhase $evaluate)
    {
        LocalInspectGuard::assertMutable();
        $evaluate->execute($world);

        return redirect()
            ->route('dev.inspect.apocalypse', $world)
            ->with('status', 'Phase evaluated.');
    }
}
