<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'description' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
