<?php

namespace App\Http\Controllers;

use App\Models\Team;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Appello degli allenamenti: per ogni data, chi era presente e chi assente.
 */
class AttendanceController extends Controller
{
    /** Giorni all'indietro in cui proporre gli allenamenti in calendario. */
    private const RECENT_DAYS = 27;

    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        $teams = Team::with('tournament')
            ->withCount('players')
            ->withMax('attendances', 'date')
            ->when(! $user->isAdmin(), fn ($q) => $q->whereRelation('coaches', 'users.id', $user->id))
            ->orderBy('name')
            ->get();

        if (! $user->isAdmin() && $teams->count() === 1) {
            return redirect()->route('teams.attendances.show', $teams->first());
        }

        return view('attendances.index', ['teams' => $teams]);
    }

    public function show(Team $team): View
    {
        Gate::authorize('manageRoster', $team);

        $team->load(['tournament', 'trainings', 'players' => fn ($q) => $q->orderBy('last_name')->orderBy('first_name')]);
        $attendances = $team->attendances()->get();
        $byDate = $attendances->groupBy(fn ($a) => $a->date->toDateString());

        // Allenamenti in calendario delle ultime quattro settimane, più le date con un appello già fatto.
        $today = CarbonImmutable::today();
        $dates = collect(range(0, self::RECENT_DAYS))
            ->map(fn (int $days) => $today->subDays($days))
            ->filter(fn (CarbonImmutable $date) => $team->trainingsOn($date)->isNotEmpty())
            ->map->toDateString()
            ->merge($byDate->keys())
            ->unique()
            ->sortDesc()
            ->values();

        return view('attendances.show', [
            'team' => $team,
            'sessions' => $dates->map(fn (string $date) => (object) [
                'date' => CarbonImmutable::parse($date),
                'trainings' => $team->trainingsOn(CarbonImmutable::parse($date)),
                'present' => $byDate->get($date)?->where('present', true)->count(),
                'total' => $byDate->get($date)?->count(),
            ]),
            'summary' => $this->summary($team, $attendances),
        ]);
    }

    public function edit(Request $request, Team $team): View
    {
        Gate::authorize('manageRoster', $team);

        $date = $this->date($request, $team);
        $team->load(['tournament', 'trainings', 'players' => fn ($q) => $q->orderBy('last_name')->orderBy('first_name')]);
        $saved = $team->attendances()->whereDate('date', $date)->get()->keyBy('player_id');

        return view('attendances.edit', [
            'team' => $team,
            'date' => $date,
            'trainings' => $team->trainingsOn($date),
            'saved' => $saved,
        ]);
    }

    public function update(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('manageRoster', $team);

        $date = $this->date($request, $team);
        $playerIds = $team->players()->pluck('id');
        $data = $request->validate([
            'players' => ['required', 'array'],
            'players.*' => ['required', 'in:0,1'],
        ], [
            'players.required' => 'Non ci sono calciatori da segnare.',
        ]);

        $saved = $team->attendances()->whereDate('date', $date)->get()->keyBy('player_id');

        foreach ($data['players'] as $playerId => $present) {
            if (! $playerIds->contains((int) $playerId)) {
                continue;
            }

            // whereDate e non updateOrCreate: SQLite salva la data con l'ora e il confronto esatto fallirebbe.
            $row = $saved->get($playerId) ?? $team->attendances()->make(['player_id' => $playerId, 'date' => $date]);
            $row->present = (bool) $present;
            $row->save();
        }

        return redirect()->route('teams.attendances.show', $team)
            ->with('status', 'Appello del '.$date->format('d/m/Y').' salvato.');
    }

    public function destroy(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('manageRoster', $team);

        $date = $this->date($request, $team, scheduled: false);
        $team->attendances()->whereDate('date', $date)->delete();

        return redirect()->route('teams.attendances.show', $team)
            ->with('status', 'Appello del '.$date->format('d/m/Y').' cancellato.');
    }

    /**
     * Data dell'appello, dal campo "data": non nel futuro e in un giorno di allenamento della squadra.
     */
    private function date(Request $request, Team $team, bool $scheduled = true): CarbonImmutable
    {
        $request->validate([
            'data' => ['required', 'date_format:Y-m-d', 'before_or_equal:today'],
        ], [
            'data.required' => 'Scegli la data dell\'allenamento.',
            'data.date_format' => 'La data non è valida.',
            'data.before_or_equal' => 'Non puoi fare l\'appello di un allenamento futuro.',
        ]);

        $date = CarbonImmutable::createFromFormat('!Y-m-d', $request->input('data'));

        // Niente date inventate: l'appello si fa solo nei giorni degli allenamenti inseriti
        // (un appello già salvato resta modificabile anche se poi l'orario è stato tolto).
        if ($scheduled && $team->trainingsOn($date)->isEmpty() && ! $team->attendances()->whereDate('date', $date)->exists()) {
            throw ValidationException::withMessages([
                'data' => 'In questo giorno la squadra non ha allenamenti in calendario.',
            ]);
        }

        return $date;
    }

    /**
     * Presenze di ogni calciatore della rosa sul totale degli appelli in cui c'era.
     *
     * @return Collection<int, object>
     */
    private function summary(Team $team, Collection $attendances): Collection
    {
        $byPlayer = $attendances->groupBy('player_id');

        return $team->players->map(function ($player) use ($byPlayer) {
            $rows = $byPlayer->get($player->id, collect());
            $present = $rows->where('present', true)->count();

            return (object) [
                'player' => $player,
                'present' => $present,
                'absent' => $rows->count() - $present,
                'percent' => $rows->isEmpty() ? null : (int) round($present / $rows->count() * 100),
            ];
        });
    }
}
