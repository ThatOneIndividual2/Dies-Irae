<?php

use App\Http\Controllers\Dev\SpiritualInspectController;
use Illuminate\Support\Facades\Route;

if (app()->environment('local', 'testing')) {
    Route::prefix('dev/inspect/spiritual')->name('dev.inspect.spiritual.')->group(function () {
        Route::get('/characters/{character}', [SpiritualInspectController::class, 'character'])->name('character');
        Route::get('/subjects/{type}/{id}', [SpiritualInspectController::class, 'subject'])->name('subject');
    });
}
