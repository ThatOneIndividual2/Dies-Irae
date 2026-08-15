<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\Dev\ApocalypseInspectController;
use App\Http\Controllers\Dev\EventInspectController;
use App\Http\Controllers\Dev\InspectController;
use App\Http\Controllers\Dev\NamedInfernalInspectController;
use App\Http\Controllers\Dev\SacredInspectController;
use App\Http\Controllers\Dev\SpiritualInspectController;
use App\Http\Controllers\PlayController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check() ? redirect()->route('dashboard') : redirect()->route('login');
});

Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [PlayController::class, 'dashboard'])->name('dashboard');
    Route::get('/character', [PlayController::class, 'character'])->name('character');
    Route::get('/dynasty', [PlayController::class, 'dynasty'])->name('dynasty');
    Route::get('/titles', [PlayController::class, 'titles'])->name('titles');
    Route::get('/realm', [PlayController::class, 'realm'])->name('realm');
    Route::get('/map', [PlayController::class, 'map'])->name('map');
    Route::get('/settlements/{territory}', [PlayController::class, 'settlement'])->name('settlement');
    Route::get('/church', [PlayController::class, 'church'])->name('church');
    Route::get('/spiritual', [PlayController::class, 'spiritual'])->name('spiritual');
    Route::get('/plague', [PlayController::class, 'plague'])->name('plague');
    Route::get('/army', [PlayController::class, 'army'])->name('army');
    Route::get('/apocalypse', [PlayController::class, 'apocalypse'])->name('apocalypse');
    Route::get('/events', [PlayController::class, 'events'])->name('events');
    Route::post('/time/advance', [PlayController::class, 'advance'])->name('time.advance');
    Route::post('/events/{event}/resolve', [PlayController::class, 'resolveEvent'])->name('events.resolve');
    Route::post('/army/raise', [PlayController::class, 'raiseArmy'])->name('army.raise');
    Route::post('/army/{army}/move', [PlayController::class, 'moveArmy'])->name('army.move');
    Route::post('/army/{army}/fight', [PlayController::class, 'fight'])->name('army.fight');
});

if (app()->environment(['local', 'testing'])) {
    Route::middleware('local.inspect')->prefix('dev/inspect')->name('dev.inspect.')->group(function () {
        Route::get('/', [InspectController::class, 'index'])->name('index');
        Route::get('/worlds/{world}', [InspectController::class, 'show'])->name('show');
        Route::get('/apocalypse/{world}', [ApocalypseInspectController::class, 'show'])->name('apocalypse');
        Route::post('/apocalypse/{world}/signal', [ApocalypseInspectController::class, 'signal'])->name('apocalypse.signal');
        Route::post('/apocalypse/{world}/tick', [ApocalypseInspectController::class, 'tick'])->name('apocalypse.tick');
        Route::post('/apocalypse/{world}/evaluate', [ApocalypseInspectController::class, 'evaluate'])->name('apocalypse.evaluate');
        Route::get('/events/{world}', [EventInspectController::class, 'show'])->name('events');
        Route::post('/events/{world}/pulse', [EventInspectController::class, 'pulse'])->name('events.pulse');
        Route::post('/events/{world}/force', [EventInspectController::class, 'force'])->name('events.force');
        Route::get('/named/{world}', [NamedInfernalInspectController::class, 'show'])->name('named');
        Route::get('/spiritual/characters/{character}', [SpiritualInspectController::class, 'character'])->name('spiritual.character');
        Route::get('/spiritual/subjects/{type}/{id}', [SpiritualInspectController::class, 'subject'])->name('spiritual.subject');
        Route::get('/sacred/saints/{saint}', [SacredInspectController::class, 'saint'])->name('sacred.saint');
        Route::get('/sacred/relics/{relic}', [SacredInspectController::class, 'relic'])->name('sacred.relic');
        Route::get('/sacred/routes/{route}', [SacredInspectController::class, 'route'])->name('sacred.route');
        Route::get('/sacred/miracles/{miracle}', [SacredInspectController::class, 'miracle'])->name('sacred.miracle');
    });
}
