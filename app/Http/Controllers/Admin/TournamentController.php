<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TournamentController extends Controller
{
    public function index(): View
    {
        return view('tournaments.index', [
            'tournaments' => Tournament::withCount(['teams', 'games'])->latest('starts_on')->get(),
        ]);
    }

    public function create(): View
    {
        return view('tournaments.form', ['tournament' => new Tournament]);
    }

    public function store(Request $request): RedirectResponse
    {
        Tournament::create($this->validated($request));

        return redirect()->route('tournaments.index')->with('status', 'Torneo creato.');
    }

    public function edit(Tournament $tournament): View
    {
        return view('tournaments.form', ['tournament' => $tournament]);
    }

    public function update(Request $request, Tournament $tournament): RedirectResponse
    {
        $tournament->update($this->validated($request));

        return redirect()->route('tournaments.index')->with('status', 'Torneo aggiornato.');
    }

    public function destroy(Tournament $tournament): RedirectResponse
    {
        // Le squadre spariscono a cascata nel database: i loro stemmi vanno cancellati a mano.
        Storage::disk('uploads')->delete($tournament->teams()->whereNotNull('logo')->pluck('logo')->all());
        $tournament->delete();

        return redirect()->route('tournaments.index')->with('status', 'Torneo eliminato, con le sue squadre e partite.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'season' => ['nullable', 'string', 'max:20'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'lineup_max_players' => ['nullable', 'integer', 'min:1', 'max:40'],
            'description' => ['nullable', 'string', 'max:2000'],
        ], [
            'lineup_max_players.*' => 'Il massimo in distinta va da 1 a 40 giocatori.',
        ]);
    }
}
