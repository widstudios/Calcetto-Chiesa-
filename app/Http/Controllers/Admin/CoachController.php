<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Account degli allenatori: con l'accesso possono gestire la rosa della propria squadra.
 */
class CoachController extends Controller
{
    public function index(): View
    {
        return view('coaches.index', [
            'coaches' => User::where('role', Role::Allenatore)->with('teams')->orderBy('name')->get(),
        ]);
    }

    public function create(): View
    {
        return view('coaches.form', ['coach' => new User, 'teams' => Team::with('tournament')->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $coach = User::create([...$data, 'role' => Role::Allenatore]);
        $this->assignTeams($coach, $request);

        return redirect()->route('coaches.index')->with('status', 'Allenatore creato: ora può accedere con la sua email.');
    }

    public function edit(User $coach): View
    {
        abort_unless($coach->role === Role::Allenatore, 404);

        return view('coaches.form', ['coach' => $coach, 'teams' => Team::with('tournament')->orderBy('name')->get()]);
    }

    public function update(Request $request, User $coach): RedirectResponse
    {
        abort_unless($coach->role === Role::Allenatore, 404);

        $data = $this->validated($request, $coach);
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $coach->update($data);
        $this->assignTeams($coach, $request);

        return redirect()->route('coaches.index')->with('status', 'Allenatore aggiornato.');
    }

    public function destroy(User $coach): RedirectResponse
    {
        abort_unless($coach->role === Role::Allenatore, 404);

        $coach->delete();

        return redirect()->route('coaches.index')->with('status', 'Account allenatore eliminato.');
    }

    private function assignTeams(User $coach, Request $request): void
    {
        $teamIds = $request->validate(['teams' => ['array'], 'teams.*' => ['integer', 'exists:teams,id']])['teams'] ?? [];

        Team::where('coach_id', $coach->id)->whereNotIn('id', $teamIds)->update(['coach_id' => null]);
        Team::whereIn('id', $teamIds)->update(['coach_id' => $coach->id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?User $coach = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($coach)],
            'password' => [$coach ? 'nullable' : 'required', 'string', Password::min(8)],
        ]);
    }
}
