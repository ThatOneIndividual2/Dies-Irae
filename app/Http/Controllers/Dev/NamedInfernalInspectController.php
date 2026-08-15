<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\NamedDemon;
use App\Models\World;
use Illuminate\View\View;

class NamedInfernalInspectController extends Controller
{
    public function show(World $world): View
    {
        $demons = NamedDemon::query()
            ->where('world_id', $world->id)
            ->with(['objectives', 'servants', 'grudges', 'acts', 'knowledge', 'influence', 'faction'])
            ->orderBy('id')
            ->get();

        return view('dev.inspect.named-infernal', compact('world', 'demons'));
    }
}
