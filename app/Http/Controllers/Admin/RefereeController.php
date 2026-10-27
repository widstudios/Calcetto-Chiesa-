<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GameStatus;
use App\Enums\Role;
use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Referee;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/**
 * Arbitri: nome, cognome, telefono e partite che dirigono. Solo per l'admin.
 * Con email e password l'arbitro accede all'app e vede, senza modificarle, solo le sue partite.
 */
class RefereeController extends Controller
{
    public function index(): View
    {
        return view('referees.index', [
            'referees' => Referee::with(['user', 'games' => fn ($query) => $query->with(['tournament', 'homeTeam', 'awayTeam'])->orderBy('kickoff_at')])
                ->orderBy('last_name')->orderBy('first_name')->get(),
            'unassigned' => Game::whereNull('referee_id')->where('status', GameStatus::Programmata)->count(),
        ]);
    }

    public function create(): View
    {
        return view('referees.form', ['referee' => new Referee, 'games' => $this->assignableGames(), 'hasUpcoming' => $this->hasUpcomingGames()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($data) {
            $referee = Referee::create($data);
            $this->assignGames($referee, $data['games']);
            $this->syncAccount($referee, $data);
        });

        return redirect()->route('referees.index')->with('status', 'Arbitro aggiunto.');
    }

    public function edit(Referee $referee): View
    {
        return view('referees.form', ['referee' => $referee, 'games' => $this->assignableGames($referee), 'hasUpcoming' => $this->hasUpcomingGames()]);
    }

    public function update(Request $request, Referee $referee): RedirectResponse
    {
        $data = $this->validated($request, $referee);
        DB::transaction(function () use ($referee, $data) {
            $referee->update($data);
            $this->assignGames($referee, $data['games']);
            $this->syncAccount($referee, $data);
        });

        return redirect()->route('referees.index')->with('status', 'Arbitro aggiornato.');
    }

    public function destroy(Referee $referee): RedirectResponse
    {
        // Le sue partite restano in calendario, senza arbitro; il suo accesso all'app sparisce.
        DB::transaction(function () use ($referee) {
            $user = $referee->user;
            $referee->delete();
            $user?->delete();
        });

        return redirect()->route('referees.index')->with('status', 'Arbitro eliminato.');
    }

    /**
     * Partite da arbitrare: quelle ancora da giocare senza arbitro, più quelle già di questo arbitro.
     *
     * @return Collection<int, Game>
     */
    private function assignableGames(?Referee $referee = null): Collection
    {
        return Game::with(['tournament', 'homeTeam', 'awayTeam'])
            ->where(fn ($query) => $query
                ->where(fn ($free) => $free->whereNull('referee_id')->where('status', '!=', GameStatus::Giocata))
                ->when($referee?->exists, fn ($query) => $query->orWhere('referee_id', $referee->id)))
            ->orderByRaw('kickoff_at is null')->orderBy('kickoff_at')->orderBy('matchday')
            ->get();
    }

    /**
     * Ci sono partite ancora da giocare? Se no, prima vanno create in Calendario.
     */
    private function hasUpcomingGames(): bool
    {
        return Game::where('status', '!=', GameStatus::Giocata)->exists();
    }

    /**
     * @param  array<int, int|string>  $gameIds
     */
    private function assignGames(Referee $referee, array $gameIds): void
    {
        $referee->games()->whereNotIn('id', $gameIds)->update(['referee_id' => null]);
        Game::whereIn('id', $gameIds)->update(['referee_id' => $referee->id]);
    }

    /**
     * Email compilata: l'arbitro ha (o riceve) l'account per accedere. Email vuota: l'accesso viene tolto.
     *
     * @param  array<string, mixed>  $data
     */
    private function syncAccount(Referee $referee, array $data): void
    {
        $user = $referee->user;
        if (blank($data['email'])) {
            $user?->delete();

            return;
        }

        $account = ['name' => $referee->fullName(), 'email' => $data['email']];
        if (filled($data['password'])) {
            $account['password'] = $data['password'];
        }

        if ($user) {
            $user->update($account);
        } else {
            $referee->user()->associate(User::create([...$account, 'role' => Role::Arbitro]))->save();
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?Referee $referee = null): array
    {
        $hasAccount = $referee?->user !== null;

        // Tutto validato prima di salvare: un errore sulle partite non lascia un arbitro a metà.
        return $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[\d\s+().\/-]+$/'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users')->ignore($referee?->user_id)],
            // Per dare l'accesso la prima volta serve la password; dopo si cambia solo se compilata.
            'password' => [$hasAccount ? 'nullable' : 'required_with:email', 'nullable', 'string', Password::min(8)],
            'games' => ['array'],
            'games.*' => ['integer', 'exists:games,id'],
        ], [
            'phone.regex' => 'Il telefono può contenere solo numeri, spazi e il segno +.',
            'password.required_with' => 'Scegli una password per dare all\'arbitro l\'accesso all\'app.',
        ]) + ['games' => [], 'email' => null, 'password' => null];
    }
}
