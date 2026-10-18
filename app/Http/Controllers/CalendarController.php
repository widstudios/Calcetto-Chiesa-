<?php

namespace App\Http\Controllers;

use App\Enums\EventType;
use App\Enums\GameStatus;
use App\Models\CalendarEvent;
use App\Models\Game;
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
 * Calendario settimanale per allenatori e admin: allenamenti, partite ed eventi liberi insieme.
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

        $items = $this->trainings($teams->whereIn('id', $ids), $start)
            ->concat($this->games($user, $ids, $start, $end))
            ->concat($this->events($user, $ids, $start, $end));

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
     * Allenamenti settimanali delle squadre, nei giorni di questa settimana.
     *
     * @param  Collection<int, Team>  $teams
     * @return Collection<int, object>
     */
    private function trainings(Collection $teams, CarbonImmutable $start): Collection
    {
        $today = CarbonImmutable::today();

        return $teams->load('trainings')->flatMap(fn (Team $team) => $team->trainings
            // Orari doppi (stesso giorno, orario e campo) si mostrano una volta sola.
            ->unique(fn ($slot) => $slot->weekday.'|'.$slot->timeRange().'|'.$slot->field)
            ->map(function ($slot) use ($team, $start, $today) {
                $date = $start->addDays($slot->weekday - 1);

                return (object) [
                    'date' => $date,
                    'start' => substr($slot->starts_at, 0, 5),
                    'time' => $slot->timeRange(),
                    'type' => 'allenamento',
                    'label' => 'Allenamento',
                    'title' => 'Allenamento '.$team->name,
                    'team' => $team->name,
                    'place' => $slot->field,
                    'notes' => null,
                    'links' => [$date->lte($today)
                        ? ['Appello', route('teams.attendances.edit', [$team, 'data' => $date->toDateString()]), 'checklist']
                        : ['Presenze', route('teams.attendances.show', $team), 'checklist']],
                ];
            }));
    }

    /**
     * Partite della settimana in cui gioca una delle squadre.
     *
     * @param  Collection<int, int>  $ids
     * @return Collection<int, object>
     */
    private function games(User $user, Collection $ids, CarbonImmutable $start, CarbonImmutable $end): Collection
    {
        return Game::with(['tournament', 'homeTeam', 'awayTeam'])
            ->whereBetween('kickoff_at', [$start->startOfDay(), $end->endOfDay()])
            ->where(fn ($q) => $q->whereIn('home_team_id', $ids)->orWhereIn('away_team_id', $ids))
            ->get()
            ->map(function (Game $game) use ($user, $ids) {
                $mine = $ids->contains($game->home_team_id) ? $game->homeTeam : $game->awayTeam;
                $links = $user->isAdmin()
                    ? [['Modifica', route('games.edit', $game), 'pencil'], ['Tabellino', route('games.sheet.edit', $game), 'ball']]
                    : [['Distinta', route('teams.lineups.create', [$mine, 'partita' => $game->id]), 'clipboard']];

                return (object) [
                    'date' => CarbonImmutable::instance($game->kickoff_at)->startOfDay(),
                    'start' => $game->kickoff_at->format('H:i'),
                    'time' => $game->kickoff_at->format('H:i'),
                    'type' => 'partita',
                    'label' => $game->status === GameStatus::Programmata ? 'Partita' : 'Partita '.strtolower($game->status->label()),
                    'title' => $game->homeTeam->name.($game->isPlayed() ? " {$game->home_score}-{$game->away_score} " : ' - ').$game->awayTeam->name,
                    'team' => $game->tournament->name.($game->matchday ? ' · '.$game->matchday.'ª giornata' : ''),
                    'place' => $game->venue,
                    'notes' => null,
                    'links' => $links,
                ];
            });
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
