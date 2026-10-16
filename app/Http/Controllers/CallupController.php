<?php

namespace App\Http\Controllers;

use App\Models\Callup;
use App\Models\Game;
use App\Models\Team;
use App\Models\Tournament;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\View\View;

/**
 * Distinta di gara: i convocati di una squadra per una partita, da stampare.
 */
class CallupController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
        $teamIds = $user->isAdmin() ? null : $user->teams()->pluck('teams.id');
        $tournaments = $user->isAdmin() ? Tournament::orderBy('name')->get() : collect();
        $tournament = $tournaments->firstWhere('id', $request->integer('tournament')) ?? $tournaments->first();

        $games = Game::with(['tournament', 'homeTeam', 'awayTeam'])
            ->withCount([
                'callups as home_callups_count' => fn ($q) => $q->whereColumn('callups.team_id', 'games.home_team_id'),
                'callups as away_callups_count' => fn ($q) => $q->whereColumn('callups.team_id', 'games.away_team_id'),
            ])
            ->when($teamIds, fn ($q) => $q->where(fn ($q) => $q->whereIn('home_team_id', $teamIds)->orWhereIn('away_team_id', $teamIds)))
            ->when($tournament, fn ($q) => $q->where('tournament_id', $tournament->id))
            // Dalle partite di ieri in poi: la distinta serve prima della gara.
            ->where(fn ($q) => $q->whereNull('kickoff_at')->orWhere('kickoff_at', '>=', now()->subDay()->startOfDay()))
            ->orderByRaw('kickoff_at is null')
            ->orderBy('kickoff_at')
            ->orderBy('matchday')
            ->get();

        return view('callups.index', [
            'games' => $games,
            'tournaments' => $tournaments,
            'tournament' => $tournament,
            'myTeams' => $teamIds,
        ]);
    }

    public function edit(Game $game, Team $team): View
    {
        $this->authorizeTeam($game, $team);

        $game->load(['tournament', 'homeTeam', 'awayTeam']);
        $team->load(['players' => fn ($q) => $q->orderByRaw("squad_role = 'panchina'")->orderByRaw('shirt_number is null')->orderBy('shirt_number')->orderBy('last_name')]);
        $saved = $game->callups()->where('team_id', $team->id)->get()->keyBy('player_id');

        return view('callups.edit', [
            'game' => $game,
            'team' => $team,
            'saved' => $saved,
            // Distinta nuova: proposti tutti i calciatori della prima squadra.
            'isNew' => $saved->isEmpty(),
        ]);
    }

    public function update(Request $request, Game $game, Team $team): RedirectResponse
    {
        $this->authorizeTeam($game, $team);

        $playerIds = $team->players()->pluck('id')->all();
        $data = $request->validate([
            'players' => ['nullable', 'array'],
            'players.*.called' => ['nullable', 'boolean'],
            'players.*.shirt_number' => ['nullable', 'integer', 'min:1', 'max:99'],
            'captain' => ['nullable', Rule::in($playerIds)],
            'vice' => ['nullable', Rule::in($playerIds), 'different:captain'],
        ], [
            'players.*.shirt_number.*' => 'Il numero di maglia va da 1 a 99.',
            'captain.in' => 'Il capitano deve essere della rosa.',
            'vice.in' => 'Il vice capitano deve essere della rosa.',
            'vice.different' => 'Capitano e vice devono essere due calciatori diversi.',
        ]);

        $called = collect($data['players'] ?? [])
            ->filter(fn ($row, $id) => ($row['called'] ?? false) && in_array((int) $id, $playerIds, true));

        validator($data)->after(function (Validator $validator) use ($called, $data) {
            if ($called->isEmpty()) {
                $validator->errors()->add('players', 'Seleziona almeno un convocato.');
            }
            $numbers = $called->pluck('shirt_number')->filter();
            if ($numbers->count() !== $numbers->unique()->count()) {
                $validator->errors()->add('players', 'Due convocati hanno lo stesso numero di maglia: '.$numbers->duplicates()->unique()->join(', ').'.');
            }
            foreach (['captain' => 'Il capitano', 'vice' => 'Il vice capitano'] as $field => $label) {
                if (filled($data[$field] ?? null) && ! $called->has($data[$field])) {
                    $validator->errors()->add($field, $label.' deve essere tra i convocati.');
                }
            }
        })->validate();

        $game->callups()->where('team_id', $team->id)->delete();
        foreach ($called as $playerId => $row) {
            $game->callups()->create([
                'team_id' => $team->id,
                'player_id' => $playerId,
                'shirt_number' => $row['shirt_number'] ?? null,
                'captain' => match ((string) $playerId) {
                    (string) ($data['captain'] ?? '') => Callup::CAPTAIN,
                    (string) ($data['vice'] ?? '') => Callup::VICE,
                    default => null,
                },
            ]);
        }

        return redirect()->route('games.callups.print', [$game, $team])->with('status', 'Distinta salvata: ora puoi stamparla.');
    }

    public function print(Game $game, Team $team): View
    {
        $this->authorizeTeam($game, $team);

        $game->load(['tournament', 'homeTeam', 'awayTeam']);
        $team->load('coaches');

        return view('callups.print', [
            'game' => $game,
            'team' => $team,
            'callups' => $game->callups()->where('team_id', $team->id)->with('player')->get()
                ->sortBy(fn (Callup $c) => sprintf('%03d-%s', $c->shirt_number ?? 999, $c->player->last_name))
                ->values(),
        ]);
    }

    private function authorizeTeam(Game $game, Team $team): void
    {
        abort_unless($game->involves($team), 404);
        Gate::authorize('manageRoster', $team);
    }
}
