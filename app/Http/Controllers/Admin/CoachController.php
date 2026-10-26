<?php

namespace App\Http\Controllers\Admin;

use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Account degli allenatori: con l'accesso possono gestire la rosa della propria squadra.
 * Qui si segue anche il loro tesseramento CSI.
 */
class CoachController extends Controller
{
    public function index(): View
    {
        $coaches = User::coaches()->with('teams')->orderBy('name')->get();
        $namedTeams = Team::whereNotNull('coach_name')->orderBy('name')->get();

        return view('coaches.index', [
            'coaches' => $coaches,
            // Squadre di ogni allenatore: quelle collegate al suo account e quelle che lo citano per cognome.
            'teamNames' => $coaches->mapWithKeys(fn (User $coach) => [$coach->id => $coach->teams
                ->merge($namedTeams->filter(fn (Team $team) => $this->mentions($team->coach_name, $coach->name)))
                ->unique('id')->pluck('name')->all()]),
        ]);
    }

    public function create(): View
    {
        return view('coaches.form', ['coach' => new User, 'teams' => Team::with('tournament')->orderBy('name')->get()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);

        $canLogIn = filled($data['password'] ?? null);
        // Senza password l'allenatore resta in elenco (es. per il tesseramento) ma non può accedere.
        $coach = User::create([...$data, 'password' => $canLogIn ? $data['password'] : Str::random(40), 'role' => Role::Allenatore]);
        $this->assignTeams($coach, $request);

        return redirect()->route('coaches.index')->with('status', $canLogIn
            ? 'Allenatore creato: ora può accedere con la sua email.'
            : 'Allenatore aggiunto. Per farlo accedere, impostagli una password da Modifica.');
    }

    public function edit(User $coach): View
    {
        abort_unless($coach->isCoach(), 404);

        return view('coaches.form', ['coach' => $coach, 'teams' => Team::with('tournament')->orderBy('name')->get()]);
    }

    public function update(Request $request, User $coach): RedirectResponse
    {
        abort_unless($coach->isCoach(), 404);

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
        abort_unless($coach->isCoach(), 404);

        // Un admin che allena resta admin: perde solo il ruolo di allenatore e le sue squadre.
        if ($coach->isAdmin()) {
            $coach->teams()->detach();
            $coach->update(['is_coach' => false]);

            return redirect()->route('coaches.index')->with('status', $coach->name.' non è più allenatore: resta amministratore.');
        }

        $coach->delete();

        return redirect()->route('coaches.index')->with('status', 'Account allenatore eliminato.');
    }

    /**
     * Il nome scritto nella squadra (es. "Piero Perilli, Simone Poggi") contiene il cognome dell'allenatore.
     */
    private function mentions(string $teamCoaches, string $coachName): bool
    {
        $surname = Str::afterLast(trim($coachName), ' ');

        return mb_strlen($surname) > 2 && preg_match('/\b'.preg_quote($surname, '/').'\b/iu', $teamCoaches) === 1;
    }

    private function assignTeams(User $coach, Request $request): void
    {
        $teamIds = $request->validate(['teams' => ['array'], 'teams.*' => ['integer', 'exists:teams,id']])['teams'] ?? [];

        // Tocca solo le squadre di questo allenatore: gli altri allenatori delle stesse squadre restano.
        $coach->teams()->sync($teamIds);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?User $coach = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($coach)],
            'password' => ['nullable', 'string', Password::min(8)],
            'csi_registered_on' => ['nullable', 'date', 'before_or_equal:today'],
        ]);
    }
}
