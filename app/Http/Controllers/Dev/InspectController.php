<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use App\Models\World;
use App\Validation\WorldIntegrityValidator;
use Illuminate\View\View;

class InspectController extends Controller
{
    public function index(): View
    {
        return view('dev.inspect.index', [
            'worlds' => World::query()->with('apocalypseState')->orderBy('id')->get(),
        ]);
    }

    public function show(World $world, WorldIntegrityValidator $validator): View
    {
        $world->load([
            'characters.dynasty',
            'titles.currentOwnership.holder',
            'realms.topLiege',
            'territories.holdings',
            'apocalypseState',
        ]);

        return view('dev.inspect.show', [
            'world' => $world,
            'report' => $validator->validate($world),
        ]);
    }
}
