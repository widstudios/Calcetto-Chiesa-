<?php

namespace App\Http\Controllers;

use App\Enums\SquadRole;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PlayerController extends Controller
{
    public function create(Team $team): View
    {
        Gate::authorize('manageRoster', $team);

        return view('players.form', ['team' => $team, 'player' => new Player(['squad_role' => SquadRole::Titolare])]);
    }

    public function store(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('manageRoster', $team);

        $team->players()->create($this->validated($request, $team));

        $next = $request->boolean('another') ? route('teams.players.create', $team) : route('teams.show', $team);

        return redirect($next)->with('status', 'Calciatore aggiunto.');
    }

    public function edit(Team $team, Player $player): View
    {
        Gate::authorize('manageRoster', $team);

        return view('players.form', ['team' => $team, 'player' => $player]);
    }

    public function update(Request $request, Team $team, Player $player): RedirectResponse
    {
        Gate::authorize('manageRoster', $team);

        $player->update($this->validated($request, $team, $player));

        return redirect()->route('teams.show', $team)->with('status', 'Calciatore aggiornato.');
    }

    public function destroy(Team $team, Player $player): RedirectResponse
    {
        Gate::authorize('manageRoster', $team);

        $player->delete();

        return redirect()->route('teams.show', $team)->with('status', 'Calciatore tolto dalla rosa.');
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, Team $team, ?Player $player = null): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'birth_year' => ['required', 'integer', 'min:1940', 'max:'.now()->year],
            'phone' => ['nullable', 'string', 'max:30', 'regex:'.Player::PHONE_PATTERN],
            'phone_2' => ['nullable', 'string', 'max:30', 'regex:'.Player::PHONE_PATTERN],
            'shirt_number' => [
                'nullable', 'integer', 'min:1', 'max:99',
                Rule::unique('players')->where('team_id', $team->id)->ignore($player),
            ],
            'squad_role' => ['required', Rule::enum(SquadRole::class)],
        ], [
            'shirt_number.unique' => 'Questo numero di maglia è già usato in squadra.',
            'phone.regex' => 'Scrivi solo il numero, es. 333 123 4567.',
            'phone_2.regex' => 'Scrivi solo il numero, es. 333 123 4567.',
        ]);
    }
}
