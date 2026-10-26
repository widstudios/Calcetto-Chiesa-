<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GameStatus;
use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Referee;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Arbitri: nome, cognome, telefono e partite che dirigono. Solo per l'admin.
 */
class RefereeController extends Controller
{
    public function index(): View
    {
        return view('referees.index', [
            'referees' => Referee::with(['games' => fn ($query) => $query->with(['tournament', 'homeTeam', 'awayTeam'])->orderBy('kickoff_at')])
                ->orderBy('last_name')->orderBy('first_name')->get(),
            'unassigned' => Game::whereNull('referee_id')->where('status', GameStatus::Programmata)->count(),
        ]);
    }

    public function create(): View
    {
        return view('referees.form', ['referee' => new Referee, 'games' => $this->assignableGames()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $referee = Referee::create($this->validated($request));
        $this->assignGames($referee, $request);

        return redirect()->route('referees.index')->with('status', 'Arbitro aggiunto.');
    }

    public function edit(Referee $referee): View
    {
        return view('referees.form', ['referee' => $referee, 'games' => $this->assignableGames($referee)]);
    }

    public function update(Request $request, Referee $referee): RedirectResponse
    {
        $referee->update($this->validated($request));
        $this->assignGames($referee, $request);

        return redirect()->route('referees.index')->with('status', 'Arbitro aggiornato.');
    }

    public function destroy(Referee $referee): RedirectResponse
    {
        // Le sue partite restano in calendario, senza arbitro.
        $referee->delete();

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

    private function assignGames(Referee $referee, Request $request): void
    {
        $gameIds = $request->validate(['games' => ['array'], 'games.*' => ['integer', 'exists:games,id']])['games'] ?? [];

        $referee->games()->whereNotIn('id', $gameIds)->update(['referee_id' => null]);
        Game::whereIn('id', $gameIds)->update(['referee_id' => $referee->id]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[\d\s+().\/-]+$/'],
        ], [
            'phone.regex' => 'Il telefono può contenere solo numeri, spazi e il segno +.',
        ]);
    }
}
