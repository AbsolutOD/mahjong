<?php

use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::view('/', 'welcome')->name('home');

Route::get('tiles', fn (Request $request) => view('tiles', [
    'assign' => $request->boolean('assign'),
]))->name('tiles');

/** Learning the card needs no account, so the decoder sits in front of the wall. */
Route::livewire('card', 'pages::card.line-decoder')->name('card');

Route::prefix('{current_team}')
    ->middleware(['auth', 'verified', EnsureTeamMembership::class])
    ->group(function () {
        Route::view('dashboard', 'dashboard')->name('dashboard');
    });

Route::middleware(['auth'])->group(function () {
    Route::livewire('invitations/{invitation}/accept', 'pages::teams.accept-invitation')->name('invitations.accept');
});

require __DIR__.'/settings.php';
