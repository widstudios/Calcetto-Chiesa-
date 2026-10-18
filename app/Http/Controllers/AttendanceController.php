<?php

namespace App\Http\Controllers;

use App\Models\Drill;
use App\Models\Team;
use App\Support\Pdf;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

/**
 * Appello degli allenamenti: per ogni data, chi era presente e chi assente.
 */
class AttendanceController extends Controller
{
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
        $drillsByDate = $team->trainingDrills()->with('drill')->get()->groupBy(fn ($row) => $row->date->toDateString());

        // Un appello per ogni giorno di allenamento inserito: l'ultimo in cui c'è stato (oggi compreso).
        $today = CarbonImmutable::today();
        $count = fn (?Collection $rows) => $rows ? (object) ['present' => $rows->where('present', true)->count(), 'total' => $rows->count()] : null;

        return view('attendances.show', [
            'team' => $team,
            // Due orari nello stesso giorno sono un solo appello: le presenze si segnano per data.
            // Qui solo l'appello da fare: se l'ultimo è già fatto si indica il prossimo, i conteggi stanno negli appelli fatti.
            'trainings' => $team->trainings->groupBy('weekday')->map(function ($slots, $weekday) use ($today, $byDate) {
                $date = $today->subDays(($today->dayOfWeekIso - $weekday + 7) % 7);
                $done = $byDate->has($date->toDateString());

                return (object) [
                    // Orari doppi (stesso orario e campo inseriti due volte) si mostrano una volta sola.
                    'slots' => $slots->unique(fn ($slot) => $slot->timeRange().'|'.$slot->field)->values(),
                    'date' => $done ? $date->addWeek() : $date,
                    'done' => $done,
                ];
            })->values(),
            // Appelli già fatti, dal più recente.
            'history' => $byDate->sortKeysDesc()->map(fn ($rows, $date) => (object) [
                'date' => CarbonImmutable::parse($date),
                'done' => $count($rows),
                'drills' => $drillsByDate->get($date, collect())->pluck('drill')->filter()->values(),
            ])->values(),
            'summary' => $this->summary($team, $attendances),
        ]);
    }

    /**
     * Riepilogo e registro delle presenze in PDF.
     */
    public function pdf(Team $team): Response
    {
        Gate::authorize('manageRoster', $team);

        $team->load(['tournament', 'players' => fn ($q) => $q->orderBy('last_name')->orderBy('first_name')]);
        $attendances = $team->attendances()->get();

        return Pdf::download('pdf.attendances', [
            'team' => $team,
            'summary' => $this->summary($team, $attendances),
            'dates' => $attendances->map(fn ($a) => CarbonImmutable::parse($a->date->toDateString()))->unique()->sort()->values(),
            'marks' => $attendances->mapWithKeys(fn ($a) => [$a->player_id.'|'.$a->date->toDateString() => (bool) $a->present]),
            'presentByDate' => $attendances->where('present', true)->countBy(fn ($a) => $a->date->toDateString()),
        ], 'presenze '.$team->name, landscape: true);
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
            'drillOptions' => Drill::grouped(),
            'savedDrills' => $team->trainingDrills()->whereDate('date', $date)->orderBy('id')->pluck('drill_id'),
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
            'drills' => ['nullable', 'array', 'max:12'],
            'drills.*' => ['nullable', 'integer', 'exists:drills,id'],
        ], [
            'drills.*.exists' => 'Uno degli esercizi scelti non esiste più.',
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

        // Esercizi fatti: si sostituiscono tutti, senza doppioni né tendine lasciate vuote.
        $team->trainingDrills()->whereDate('date', $date)->delete();
        foreach (collect($data['drills'] ?? [])->filter()->map(fn ($id) => (int) $id)->unique() as $drillId) {
            $team->trainingDrills()->create(['drill_id' => $drillId, 'date' => $date]);
        }

        return redirect()->route('teams.attendances.show', $team)
            ->with('status', 'Appello del '.$date->format('d/m/Y').' salvato.');
    }

    public function destroy(Request $request, Team $team): RedirectResponse
    {
        Gate::authorize('manageRoster', $team);

        $date = $this->date($request, $team, scheduled: false);
        $team->attendances()->whereDate('date', $date)->delete();
        $team->trainingDrills()->whereDate('date', $date)->delete();

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
