<?php

use App\Http\Controllers\TileReferenceController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

/**
 * Anonymous v1 has no dashboard behind a login, so `/` is the whole front door:
 * the only place the product gets to say what it is before the workbench.
 *
 * `Route::view` rather than a closure because the page is a static view and
 * needs no controller. Laravel 13 does cache closure routes (it serializes them
 * through SerializableClosure), so this is not #25's `/tiles` trap.
 */
Route::view('/', 'home')->name('home');

/** Learning the card needs no account, so the decoder sits in front of the wall. */
Route::livewire('card', 'pages::card.line-decoder')->name('card');

/** Nor does matching a rack against the card, which is the same lesson with tiles in hand. */
Route::livewire('matcher', 'pages::matcher.hand-matcher')->name('matcher');

Route::get('tiles', TileReferenceController::class)->name('tiles');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::view('dashboard', 'dashboard')->name('dashboard');
    });

Route::middleware(['auth'])->group(function () {
    Route::livewire('invitations/{invitation}/accept', 'pages::teams.accept-invitation')->name('invitations.accept');
});

require __DIR__.'/settings.php';
