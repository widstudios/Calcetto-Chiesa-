<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Lineup;
use App\Models\LineupPlayer;
use App\Models\Team;
use App\Support\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;
use Illuminate\View\View;

/**
 * Distinte di una squadra: chi va in campo, con maglia e fascia, da stampare.
 * La partita del calendario è facoltativa: vale anche per amichevoli e gare fuori torneo.
 */
class LineupController extends Controller
{
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        $teams = Team::with('tournament')
            ->withCount(['players', 'lineups'])
            ->when(! $user->isAdmin(), fn ($q) => $q->whereRelation('coaches', 'users.id', $user->id))
            ->orderBy('name')
            ->get();

        if (! $user->isAdmin() && $teams->count() === 1) {
            return redirect()->route('teams.lineups.index', $teams->first());
        }

        return view('lineups.teams', ['teams' => $teams]);
    }

    public function teamIndex(Team $team): View
    {
        Gate::authorize('manageRoster', $team);

        $team->load('tournament');

        return view('lineups.index', [
            'team' => $team,
            'lineups' => $team->lineups()->with(['team', 'game.homeTeam', 'game.awayTeam'])->withCount('players')->get(),
        ]);
    }

    public function create(Request $request, Team $team): View
    {
        Gate::authorize('manageRoster', $team);

        $lineup = new Lineup(['game_id' => $team->games()->whereKey($request->integer('partita'))->value('id')]);

        return $this->form($team, $lineup);
    }

    public function store(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('manageRoster', $team);

        $lineup = $this->save($request, $team, $team->lineups()->make());

        return redirect()->route('teams.lineups.print', [$team, $lineup])->with('status', 'Distinta salvata: ora puoi stamparla.');
    }

    public function edit(Team $team, Lineup $lineup): View
    {
        Gate::authorize('manageRoster', $team);

        return $this->form($team, $lineup);
    }

    public function update(Request $request, Team $team, Lineup $lineup): RedirectResponse
    {
        Gate::authorize('manageRoster', $team);

        $this->save($request, $team, $lineup);

        return redirect()->route('teams.lineups.print', [$team, $lineup])->with('status', 'Distinta salvata: ora puoi stamparla.');
    }

    public function destroy(Team $team, Lineup $lineup): RedirectResponse
    {
        Gate::authorize('manageRoster', $team);

        $lineup->delete();

        return redirect()->route('teams.lineups.index', $team)->with('status', 'Distinta eliminata.');
    }

    public function print(Team $team, Lineup $lineup): View
    {
        Gate::authorize('manageRoster', $team);

        $lineup->load(['team.coaches', 'game.tournament', 'game.homeTeam', 'game.awayTeam']);

        return view('lineups.print', [
            'team' => $team,
            'lineup' => $lineup,
            'players' => $this->sortedPlayers($lineup),
        ]);
    }

    public function pdf(Team $team, Lineup $lineup): Response
    {
        Gate::authorize('manageRoster', $team);

        $team->load(['tournament', 'coaches']);
        $lineup->load(['team', 'game.tournament', 'game.homeTeam', 'game.awayTeam']);

        return Pdf::download('pdf.lineup', [
            'team' => $team,
            'lineup' => $lineup,
            'players' => $this->sortedPlayers($lineup),
            'max' => $team->tournament->lineupMaxPlayers(),
        ], 'distinta '.$lineup->title().' '.$lineup->kickoff_at?->format('Y-m-d'));
    }

    private function sortedPlayers(Lineup $lineup): Collection
    {
        return $lineup->players()->with('player')->get()
            ->sortBy(fn (LineupPlayer $row) => sprintf('%03d-%s', $row->shirt_number ?? 999, $row->player->last_name))
            ->values();
    }

    private function form(Team $team, Lineup $lineup): View
    {
        $team->load(['tournament', 'players' => fn ($q) => $q->orderByRaw("squad_role = 'panchina'")->orderByRaw('shirt_number is null')->orderBy('shirt_number')->orderBy('last_name')]);
        $saved = $lineup->exists ? $lineup->players()->get()->keyBy('player_id') : collect();

        return view('lineups.form', [
            'team' => $team,
            'lineup' => $lineup,
            'games' => $team->games()->with(['homeTeam', 'awayTeam'])->get(),
            'saved' => $saved,
            'max' => $team->tournament->lineupMaxPlayers(),
            // Distinta nuova: proposti tutti i calciatori della prima squadra.
            'isNew' => ! $lineup->exists,
        ]);
    }

    private function save(Request $request, Team $team, Lineup $lineup): Lineup
    {
        $playerIds = $team->players()->pluck('id')->all();
        $data = $request->validate([
            'game_id' => ['nullable', Rule::in($team->games()->pluck('id')->all())],
            'kickoff_at' => ['nullable', 'date'],
            'opponent' => ['nullable', 'string', 'max:255'],
            'venue' => ['nullable', 'string', 'max:255'],
            'players' => ['nullable', 'array'],
            'players.*.called' => ['nullable', 'boolean'],
            'players.*.shirt_number' => ['nullable', 'integer', 'min:1', 'max:99'],
            'captain' => ['nullable', Rule::in($playerIds)],
            'vice' => ['nullable', Rule::in($playerIds), 'different:captain'],
        ], [
            'game_id.in' => 'La partita scelta non è di questa squadra.',
            'kickoff_at.date' => 'La data non è valida.',
            'players.*.shirt_number.*' => 'Il numero di maglia va da 1 a 99.',
            'captain.in' => 'Il capitano deve essere della rosa.',
            'vice.in' => 'Il vice capitano deve essere della rosa.',
            'vice.different' => 'Capitano e vice devono essere due calciatori diversi.',
        ]);

        $called = collect($data['players'] ?? [])
            ->filter(fn ($row, $id) => ($row['called'] ?? false) && in_array((int) $id, $playerIds, true));

        // Regolamento: la distinta ha un numero massimo di giocatori.
        $max = $team->tournament->lineupMaxPlayers();

        validator($data)->after(function (Validator $validator) use ($called, $data, $max) {
            if ($called->isEmpty()) {
                $validator->errors()->add('players', 'Seleziona almeno un convocato.');
            }
            if ($called->count() > $max) {
                $validator->errors()->add('players', "Il regolamento ammette al massimo {$max} giocatori in distinta: ne hai spuntati {$called->count()}. Togli ".($called->count() - $max === 1 ? 'un convocato.' : ($called->count() - $max).' convocati.'));
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

        // Con una partita del calendario, data, avversario e campo vuoti si prendono da lì.
        $game = filled($data['game_id'] ?? null) ? Game::with(['homeTeam', 'awayTeam'])->find($data['game_id']) : null;
        $lineup->fill([
            'game_id' => $game?->id,
            'kickoff_at' => $data['kickoff_at'] ?? $game?->kickoff_at,
            'opponent' => $data['opponent'] ?? ($game ? ($game->home_team_id === $team->id ? $game->awayTeam->name : $game->homeTeam->name) : null),
            'venue' => $data['venue'] ?? $game?->venue,
        ]);

        DB::transaction(function () use ($lineup, $called, $data) {
            $lineup->save();
            $lineup->players()->delete();
            foreach ($called as $playerId => $row) {
                $lineup->players()->create([
                    'player_id' => $playerId,
                    'shirt_number' => $row['shirt_number'] ?? null,
                    'captain' => match ((string) $playerId) {
                        (string) ($data['captain'] ?? '') => LineupPlayer::CAPTAIN,
                        (string) ($data['vice'] ?? '') => LineupPlayer::VICE,
                        default => null,
                    },
                ]);
            }
        });

        return $lineup;
    }
}
