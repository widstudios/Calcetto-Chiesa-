<?php

namespace App\Http\Controllers\Admin;

use App\Enums\GameStatus;
use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Referee;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        return view('referees.form', ['referee' => new Referee, 'games' => $this->assignableGames(), 'hasUpcoming' => $this->hasUpcomingGames()]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        DB::transaction(fn () => $this->assignGames(Referee::create($data), $data['games']));

        return redirect()->route('referees.index')->with('status', 'Arbitro aggiunto.');
    }

    public function edit(Referee $referee): View
    {
        return view('referees.form', ['referee' => $referee, 'games' => $this->assignableGames($referee), 'hasUpcoming' => $this->hasUpcomingGames()]);
    }

    public function update(Request $request, Referee $referee): RedirectResponse
    {
        $data = $this->validated($request);
        DB::transaction(function () use ($referee, $data) {
            $referee->update($data);
            $this->assignGames($referee, $data['games']);
        });

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
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        // Tutto validato prima di salvare: un errore sulle partite non lascia un arbitro a metà.
        return $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[\d\s+().\/-]+$/'],
            'games' => ['array'],
            'games.*' => ['integer', 'exists:games,id'],
        ], [
            'phone.regex' => 'Il telefono può contenere solo numeri, spazi e il segno +.',
        ]) + ['games' => []];
    }
}
