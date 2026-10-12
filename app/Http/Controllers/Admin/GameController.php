<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GameStatus;
use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class GameController extends Controller
{
    public function index(Request $request): View
    {
        $tournaments = Tournament::orderBy('name')->get();
        $tournament = $tournaments->firstWhere('id', $request->integer('tournament')) ?? $tournaments->first();

        return view('games.index', [
            'tournaments' => $tournaments,
            'tournament' => $tournament,
            'games' => $tournament
                ? $tournament->games()->with(['homeTeam', 'awayTeam'])->orderBy('matchday')->get()
                : collect(),
        ]);
    }

    public function create(Request $request): View
    {
        $tournament = Tournament::findOrFail($request->integer('tournament'));

        return view('games.form', [
            'game' => new Game(['tournament_id' => $tournament->id, 'status' => GameStatus::Programmata]),
            'tournament' => $tournament,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tournament = Tournament::findOrFail($request->integer('tournament_id'));

        $tournament->games()->create($this->validated($request, $tournament));

        return redirect()->route('games.index', ['tournament' => $tournament->id])->with('status', 'Partita aggiunta al calendario.');
    }

    public function edit(Game $game): View
    {
        return view('games.form', ['game' => $game, 'tournament' => $game->tournament]);
    }

    public function update(Request $request, Game $game): RedirectResponse
    {
        $game->update($this->validated($request, $game->tournament));

        return redirect()->route('games.index', ['tournament' => $game->tournament_id])->with('status', 'Partita aggiornata.');
    }

    public function destroy(Game $game): RedirectResponse
    {
        $game->delete();

        return redirect()->route('games.index', ['tournament' => $game->tournament_id])->with('status', 'Partita eliminata.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, Tournament $tournament): array
    {
        $teamInTournament = Rule::exists(Team::class, 'id')->where('tournament_id', $tournament->id);

        $data = $request->validate([
            'home_team_id' => ['required', $teamInTournament],
            'away_team_id' => ['required', 'different:home_team_id', $teamInTournament],
            'matchday' => ['nullable', 'integer', 'min:1', 'max:99'],
            'kickoff_at' => ['nullable', 'date'],
            'venue' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::enum(GameStatus::class)],
            'home_score' => ['nullable', 'required_if:status,giocata', 'integer', 'min:0', 'max:99'],
            'away_score' => ['nullable', 'required_if:status,giocata', 'integer', 'min:0', 'max:99'],
        ], [
            'away_team_id.different' => 'Una squadra non può giocare contro sé stessa.',
            'home_score.required_if' => 'Inserisci il risultato della partita giocata.',
            'away_score.required_if' => 'Inserisci il risultato della partita giocata.',
        ]);

        // Il risultato ha senso solo per le partite giocate.
        if ($data['status'] !== GameStatus::Giocata->value) {
            $data['home_score'] = $data['away_score'] = null;
        }

        return $data;
    }
}
