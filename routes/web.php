<?php

use App\Http\Controllers\Admin\CoachController;
use App\Http\Controllers\Admin\GameController;
use App\Http\Controllers\Admin\TournamentController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\TeamController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/dashboard');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password', [PasswordController::class, 'update'])->name('password.update');

    // Squadre: l'admin le gestisce tutte, l'allenatore vede e modifica la rosa delle sue.
    Route::resource('teams', TeamController::class);
    Route::scopeBindings()->group(function () {
        Route::resource('teams.players', PlayerController::class)->except(['index', 'show']);
    });

    Route::middleware('role:admin')->group(function () {
        Route::resource('tournaments', TournamentController::class)->except('show');
        Route::resource('coaches', CoachController::class)->except('show')->parameters(['coaches' => 'coach']);
        Route::resource('games', GameController::class)->except('show');
    });
});
