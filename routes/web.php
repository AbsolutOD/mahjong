<?php

use App\Http\Controllers\TileReferenceController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

/** Learning the card is the whole product, so the front door opens onto it. */
Route::redirect('/', '/card')->name('home');

/** Learning the card needs no account, so the decoder sits in front of the wall. */
Route::livewire('card', 'pages::card.line-decoder')->name('card');

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
