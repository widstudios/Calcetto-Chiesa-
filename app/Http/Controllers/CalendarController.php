<?php

namespace App\Http\Controllers;

use App\Enums\EventType;
use App\Models\CalendarEvent;
use App\Models\Team;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Calendario settimanale per allenatori e admin, con gli eventi inseriti a mano (allenamenti, partite, riunioni...).
 */
class CalendarController extends Controller
{
    public function index(Request $request): View
    {
        $request->validate([
            'settimana' => ['nullable', 'date_format:Y-m-d'],
            'squadra' => ['nullable', 'integer'],
        ]);

        $user = $request->user();
        $teams = $this->teamsOf($user);
        $team = $teams->firstWhere('id', $request->integer('squadra'));
        $ids = ($team ? collect([$team]) : $teams)->pluck('id');

        $start = ($request->filled('settimana')
            ? CarbonImmutable::createFromFormat('!Y-m-d', $request->input('settimana'))
            : CarbonImmutable::today())->startOfWeek();
        $end = $start->addDays(6);

        // Solo gli eventi inseriti a mano: allenamenti e partite non si aggiungono da soli.
        $items = $this->events($user, $ids, $start, $end);

        $today = CarbonImmutable::today();

        return view('calendar.index', [
            'teams' => $teams,
            'team' => $team,
            'start' => $start,
            'end' => $end,
            'days' => collect(range(0, 6))->map(fn ($i) => (object) [
                'date' => $start->addDays($i),
                'today' => $start->addDays($i)->isSameDay($today),
                'items' => $items->filter(fn ($item) => $item->date->isSameDay($start->addDays($i)))->sortBy('start')->values(),
            ]),
        ]);
    }

    public function create(Request $request): View
    {
        Gate::authorize('create', CalendarEvent::class);

        $date = rescue(fn () => CarbonImmutable::createFromFormat('!Y-m-d', (string) $request->query('data')), null, false);
        $teams = $this->teamsOf($request->user());

        return view('calendar.form', [
            'event' => new CalendarEvent([
                'type' => EventType::Riunione,
                'date' => $date ?? CarbonImmutable::today(),
                'team_id' => $teams->firstWhere('id', $request->integer('squadra'))?->id ?? ($request->user()->isAdmin() ? null : $teams->first()?->id),
            ]),
            'teams' => $teams,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', CalendarEvent::class);

        $event = CalendarEvent::create($this->validated($request) + ['user_id' => $request->user()->id]);

        return redirect()->route('calendar.index', ['settimana' => $event->date->startOfWeek()->toDateString()])
            ->with('status', 'Evento aggiunto al calendario.');
    }

    public function edit(Request $request, CalendarEvent $event): View
    {
        Gate::authorize('update', $event);

        return view('calendar.form', ['event' => $event, 'teams' => $this->teamsOf($request->user())]);
    }

    public function update(Request $request, CalendarEvent $event): RedirectResponse
    {
        Gate::authorize('update', $event);

        $event->update($this->validated($request));

        return redirect()->route('calendar.index', ['settimana' => $event->date->startOfWeek()->toDateString()])
            ->with('status', 'Evento aggiornato.');
    }

    public function destroy(CalendarEvent $event): RedirectResponse
    {
        Gate::authorize('delete', $event);

        $week = $event->date->startOfWeek()->toDateString();
        $event->delete();

        return redirect()->route('calendar.index', ['settimana' => $week])->with('status', 'Evento eliminato.');
    }

    /**
     * Squadre che l'utente vede in calendario: tutte per l'admin, le sue per l'allenatore.
     *
     * @return Collection<int, Team>
     */
    private function teamsOf(User $user): Collection
    {
        return Team::query()
            ->when(! $user->isAdmin(), fn ($q) => $q->whereRelation('coaches', 'users.id', $user->id))
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request): array
    {
        $user = $request->user();

        return $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'type' => ['required', Rule::enum(EventType::class)],
            // L'allenatore sceglie una delle sue squadre; solo l'admin crea eventi di tutta la società.
            'team_id' => $user->isAdmin()
                ? ['nullable', 'integer', 'exists:teams,id']
                : ['required', 'integer', Rule::in($this->teamsOf($user)->pluck('id')->all())],
            'date' => ['required', 'date_format:Y-m-d'],
            'starts_at' => ['required', 'date_format:H:i'],
            'ends_at' => ['nullable', 'date_format:H:i', 'after:starts_at'],
            'location' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ], [
            'team_id.required' => 'Scegli la squadra.',
            'team_id.in' => 'Puoi creare eventi solo per le tue squadre.',
            'ends_at.after' => "L'orario di fine deve venire dopo quello di inizio.",
        ]);
    }

    /**
     * Eventi liberi della settimana: quelli delle squadre e quelli di tutta la società.
     *
     * @param  Collection<int, int>  $ids
     * @return Collection<int, object>
     */
    private function events(User $user, Collection $ids, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return CalendarEvent::with('team')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where(fn ($q) => $q->whereNull('team_id')->orWhereIn('team_id', $ids))
            ->get()
            ->map(fn (CalendarEvent $event) => (object) [
                'date' => CarbonImmutable::instance($event->date),
                'start' => substr($event->starts_at, 0, 5),
                'time' => $event->timeRange(),
                'type' => $event->type->value,
                'label' => $event->type->label(),
                'title' => $event->title,
                'team' => $event->team?->name ?? 'Tutta la società',
                'place' => $event->location,
                'notes' => $event->notes,
                'links' => $user->can('update', $event) ? [['Modifica', route('calendar.events.edit', $event), 'pencil']] : [],
            ]);
    }
}
