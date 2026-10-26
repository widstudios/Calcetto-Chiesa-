<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Tabellino di una partita giocata: marcatori, voti, portieri e migliore in campo.
 */
class GameSheetController extends Controller
{
    public function edit(Game $game): View|RedirectResponse
    {
        if (! $game->isPlayed()) {
            return $this->resultFirst($game);
        }

        $game->load(['tournament', 'performances', 'homeTeam.players', 'awayTeam.players']);

        return view('games.sheet', [
            'game' => $game,
            'performances' => $game->performances->keyBy('player_id'),
        ]);
    }

    public function update(Request $request, Game $game): RedirectResponse
    {
        if (! $game->isPlayed()) {
            return $this->resultFirst($game);
        }

        $playerIds = $game->homeTeam->players()->pluck('id')->merge($game->awayTeam->players()->pluck('id'));

        $data = $request->validate([
            'players' => ['array'],
            'players.*.goals' => ['nullable', 'integer', 'min:0', 'max:30'],
            'players.*.rating' => ['nullable', 'numeric', 'between:1,10'],
            'mvp_player_id' => ['nullable', Rule::in($playerIds->all())],
            'goalkeepers' => ['array'],
            'goalkeepers.'.$game->home_team_id => ['nullable', Rule::in($game->homeTeam->players()->pluck('id')->all())],
            'goalkeepers.'.$game->away_team_id => ['nullable', Rule::in($game->awayTeam->players()->pluck('id')->all())],
        ], [
            'goalkeepers.*' => 'Il portiere deve essere un calciatore della sua squadra.',
            'players.*.goals.*' => 'I gol devono essere un numero da 0 a 30.',
            'players.*.rating.*' => 'Il voto va da 1 a 10.',
            'mvp_player_id.in' => 'Il migliore in campo deve giocare in una delle due squadre.',
        ]);

        $keepers = array_map('intval', array_filter($data['goalkeepers'] ?? []));

        foreach ($playerIds as $playerId) {
            $row = $data['players'][$playerId] ?? [];
            $goals = (int) ($row['goals'] ?? 0);
            $rating = $row['rating'] ?? null;
            $goalkeeper = in_array($playerId, $keepers, true);

            // Una riga serve solo a chi ha segnato, ha un voto o ha giocato in porta.
            if ($goals === 0 && blank($rating) && ! $goalkeeper) {
                $game->performances()->where('player_id', $playerId)->delete();

                continue;
            }

            $game->performances()->updateOrCreate(['player_id' => $playerId], ['goals' => $goals, 'rating' => $rating, 'goalkeeper' => $goalkeeper]);
        }

        $game->update(['mvp_player_id' => $data['mvp_player_id'] ?? null]);

        return redirect()->route('games.index', ['tournament' => $game->tournament_id])->with('status', 'Tabellino salvato.');
    }

    /**
     * Il tabellino si compila solo a partita giocata: prima va messo il risultato.
     */
    private function resultFirst(Game $game): RedirectResponse
    {
        return redirect()->route('games.edit', $game)
            ->with('status', 'Prima inserisci il risultato e metti lo stato "Giocata": poi si apre il tabellino per gol e voti.');
    }
}
