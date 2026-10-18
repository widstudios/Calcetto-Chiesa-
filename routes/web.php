<?php

use App\Http\Controllers\Admin\CoachController;
use App\Http\Controllers\Admin\CsiController;
use App\Http\Controllers\Admin\GameController;
use App\Http\Controllers\Admin\GameSheetController;
use App\Http\Controllers\Admin\ImportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\TournamentController;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DrillController;
use App\Http\Controllers\LineupController;
use App\Http\Controllers\PasswordController;
use App\Http\Controllers\PlayerController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TrainingController;
use Illuminate\Support\Facades\Route;

// Sito pubblico del torneo: visibile a tutti.
Route::get('/', PublicController::class)->name('home');
Route::get('classifica/{tournament}/pdf', [PublicController::class, 'standingsPdf'])->name('standings.pdf');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'create'])->name('login');
    Route::post('/login', [LoginController::class, 'store'])->middleware('throttle:5,1');
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::get('/password', [PasswordController::class, 'edit'])->name('password.edit');
    Route::put('/password', [PasswordController::class, 'update'])->middleware('throttle:5,1')->name('password.update');

    // Squadre: l'admin le gestisce tutte, l'allenatore vede e modifica la rosa delle sue.
    Route::resource('teams', TeamController::class);
    Route::get('teams/{team}/pdf', [TeamController::class, 'pdf'])->name('teams.pdf');
    Route::scopeBindings()->group(function () {
        Route::resource('teams.players', PlayerController::class)->except(['index', 'show']);
        Route::resource('teams.trainings', TrainingController::class)->only(['store', 'destroy']);
    });

    // Appello degli allenamenti e distinte di gara: l'allenatore per le sue squadre, l'admin per tutte.
    Route::get('presenze', [AttendanceController::class, 'index'])->name('attendances.index');
    Route::get('teams/{team}/presenze', [AttendanceController::class, 'show'])->name('teams.attendances.show');
    Route::get('teams/{team}/presenze/pdf', [AttendanceController::class, 'pdf'])->name('teams.attendances.pdf');
    Route::get('teams/{team}/presenze/appello', [AttendanceController::class, 'edit'])->name('teams.attendances.edit');
    Route::put('teams/{team}/presenze/appello', [AttendanceController::class, 'update'])->name('teams.attendances.update');
    Route::delete('teams/{team}/presenze/appello', [AttendanceController::class, 'destroy'])->name('teams.attendances.destroy');
    Route::get('distinte', [LineupController::class, 'index'])->name('lineups.index');
    Route::scopeBindings()->prefix('teams/{team}/distinte')->name('teams.lineups.')->controller(LineupController::class)->group(function () {
        Route::get('/', 'teamIndex')->name('index');
        Route::get('nuova', 'create')->name('create');
        Route::post('/', 'store')->name('store');
        Route::get('{lineup}', 'edit')->name('edit');
        Route::put('{lineup}', 'update')->name('update');
        Route::delete('{lineup}', 'destroy')->name('destroy');
        Route::get('{lineup}/stampa', 'print')->name('print');
        Route::get('{lineup}/pdf', 'pdf')->name('pdf');
    });

    // Libreria allenamenti: tutti la consultano, solo l'admin la modifica.
    Route::get('esercizi', [DrillController::class, 'index'])->name('drills.index');

    Route::middleware('role:admin')->group(function () {
        Route::resource('esercizi', DrillController::class)->except(['index', 'show'])->names('drills')->parameters(['esercizi' => 'drill']);
        Route::resource('tournaments', TournamentController::class)->except('show');
        Route::resource('coaches', CoachController::class)->except('show')->parameters(['coaches' => 'coach']);
        Route::get('games/pdf/{tournament}', [GameController::class, 'pdf'])->name('games.pdf');
        Route::resource('games', GameController::class)->except('show');
        Route::get('games/{game}/tabellino', [GameSheetController::class, 'edit'])->name('games.sheet.edit');
        Route::put('games/{game}/tabellino', [GameSheetController::class, 'update'])->name('games.sheet.update');

        // Import da Excel: anteprima, poi conferma.
        Route::get('importa', [ImportController::class, 'index'])->name('imports.index');
        Route::get('comunicati-csi', [CsiController::class, 'index'])->name('csi.index');
        Route::post('comunicati-csi/sincronizza', [CsiController::class, 'sync'])->middleware('throttle:6,1')->name('csi.sync');
        Route::put('comunicati-csi/societa', [CsiController::class, 'club'])->name('csi.club');
        Route::post('comunicati-csi/rileggi', [CsiController::class, 'import'])->middleware('throttle:6,1')->name('csi.import');
        Route::get('comunicati-csi/{document}', [CsiController::class, 'show'])->name('csi.show');
        Route::get('importa/{type}/modello', [ImportController::class, 'template'])->name('imports.template');
        Route::post('importa/{type}', [ImportController::class, 'preview'])->name('imports.preview');
        Route::post('importa/{type}/conferma', [ImportController::class, 'store'])->name('imports.store');

        // Nome e logo del sito.
        Route::get('impostazioni', [SettingController::class, 'edit'])->name('settings.edit');
        Route::put('impostazioni', [SettingController::class, 'update'])->name('settings.update');
    });
});
