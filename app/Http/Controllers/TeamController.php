<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Team;
use App\Models\Tournament;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class TeamController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $teams = Team::with(['tournament', 'coach'])
            ->withCount('players')
            ->when(! $user->isAdmin(), fn ($q) => $q->where('coach_id', $user->id))
            ->when($request->integer('tournament'), fn ($q, $id) => $q->where('tournament_id', $id))
            ->orderBy('name')
            ->get();

        return view('teams.index', [
            'teams' => $teams,
            'tournaments' => $user->isAdmin() ? Tournament::orderBy('name')->get() : collect(),
        ]);
    }

    public function show(Team $team): View
    {
        Gate::authorize('view', $team);

        $team->load(['tournament', 'coach', 'players' => fn ($q) => $q->orderBy('squad_role')->orderBy('shirt_number')]);

        return view('teams.show', ['team' => $team]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', Team::class);

        return view('teams.form', [
            'team' => new Team(['tournament_id' => $request->integer('tournament') ?: null]),
            ...$this->formOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Team::class);

        $team = Team::create($this->validated($request));

        return redirect()->route('teams.show', $team)->with('status', 'Squadra creata. Ora puoi inserire i calciatori.');
    }

    public function edit(Team $team): View
    {
        Gate::authorize('update', $team);

        return view('teams.form', ['team' => $team, ...$this->formOptions()]);
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('update', $team);

        $team->update($this->validated($request, $team));

        return redirect()->route('teams.show', $team)->with('status', 'Squadra aggiornata.');
    }

    public function destroy(Team $team): RedirectResponse
    {
        Gate::authorize('delete', $team);

        $team->delete();

        return redirect()->route('teams.index')->with('status', 'Squadra eliminata.');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'tournaments' => Tournament::orderBy('name')->get(),
            'coaches' => User::where('role', Role::Allenatore)->orderBy('name')->get(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Team $team = null): array
    {
        return $request->validate([
            'tournament_id' => ['required', 'exists:tournaments,id'],
            'name' => [
                'required', 'string', 'max:255',
                Rule::unique('teams')->where('tournament_id', $request->input('tournament_id'))->ignore($team),
            ],
            'coach_name' => ['nullable', 'string', 'max:255'],
            'coach_id' => ['nullable', Rule::exists('users', 'id')->where('role', Role::Allenatore->value)],
        ], [
            'name.unique' => 'In questo torneo esiste già una squadra con questo nome.',
        ]);
    }
}
