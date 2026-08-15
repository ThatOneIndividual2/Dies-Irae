<?php

namespace App\Http\Controllers\Dev;

use App\Actions\Events\ForceTriggerEvent;
use App\Actions\Events\InspectEventEligibility;
use App\Actions\Events\PulseNarrativeEvents;
use App\Domain\Events\EventCatalog;
use App\Domain\Support\LocalInspectGuard;
use App\Models\EventChainLink;
use App\Models\EventCooldown;
use App\Models\EventHiddenLog;
use App\Models\GameEvent;
use App\Models\World;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class EventInspectController extends Controller
{
    public function show(
        World $world,
        InspectEventEligibility $inspect,
        EventCatalog $catalog
    ) {
        $report = $inspect->execute($world);
        $events = GameEvent::query()
            ->where('world_id', $world->id)
            ->where('engine', 'catalog')
            ->orderByDesc('id')
            ->limit(40)
            ->get();
        $chains = EventChainLink::query()->where('world_id', $world->id)->orderByDesc('id')->limit(40)->get();
        $cooldowns = EventCooldown::query()->where('world_id', $world->id)->orderBy('definition_key')->get();
        $hidden = EventHiddenLog::query()->where('world_id', $world->id)->orderByDesc('id')->limit(40)->get();
        $keys = array_keys($catalog->definitions());

        return view('dev.inspect.events', compact(
            'world',
            'report',
            'events',
            'chains',
            'cooldowns',
            'hidden',
            'keys'
        ));
    }

    public function pulse(World $world, PulseNarrativeEvents $pulse)
    {
        LocalInspectGuard::assertMutable();
        $pulse->execute($world);

        return redirect()->route('dev.inspect.events', $world)->with('status', 'Pulse ran.');
    }

    public function force(Request $request, World $world, ForceTriggerEvent $force)
    {
        LocalInspectGuard::assertMutable();
        $data = $request->validate([
            'definition_key' => 'required|string',
            'scope_type' => 'required|string',
            'scope_id' => 'required|integer',
        ]);
        $force->execute($world, $data['definition_key'], $data['scope_type'], (int) $data['scope_id']);

        return redirect()->route('dev.inspect.events', $world)->with('status', 'Forced '.$data['definition_key']);
    }
}
