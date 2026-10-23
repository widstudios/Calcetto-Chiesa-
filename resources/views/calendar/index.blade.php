@extends('layouts.app')

@section('title', 'Calendario')

@section('content')
    @php
        $query = fn ($week) => array_filter(['settimana' => $week->toDateString(), 'squadra' => $team?->id]);
        $legend = ['allenamento' => 'Allenamento', 'partita' => 'Partita'] + collect(App\Enums\EventType::cases())->mapWithKeys(fn ($t) => [$t->value => $t->label()])->all();
        $range = $start->month === $end->month
            ? $start->format('j').' - '.$end->locale('it')->isoFormat('D MMMM YYYY')
            : $start->locale('it')->isoFormat('D MMMM').' - '.$end->locale('it')->isoFormat('D MMMM YYYY');
    @endphp

    <div class="page-head">
        <div>
            <span class="eyebrow">{{ $team?->name ?? (auth()->user()->isAdmin() ? 'Tutte le squadre' : 'Le mie squadre') }}</span>
            <h1>Calendario</h1>
            <p class="muted">Allenamenti, partite e impegni della settimana.</p>
        </div>
        <div class="actions">
            @if (auth()->user()->isAdmin())
                <a class="btn secondary" href="{{ route('games.index') }}"><x-icon name="trophy" /> Partite</a>
            @endif
            @if (auth()->user()->isAdmin() || $teams->isNotEmpty())
                <a class="btn" href="{{ route('calendar.events.create', array_filter(['squadra' => $team?->id])) }}"><x-icon name="plus" /> Nuovo evento</a>
            @endif
        </div>
    </div>

    <div class="card flush cal-bar">
        <div class="cal-week">
            <a class="btn small secondary" href="{{ route('calendar.index', $query($start->subWeek())) }}" aria-label="Settimana precedente" title="Settimana precedente"><x-icon name="back" /></a>
            <strong>{{ $range }}</strong>
            <a class="btn small secondary" href="{{ route('calendar.index', $query($start->addWeek())) }}" aria-label="Settimana successiva" title="Settimana successiva"><x-icon name="chevron" /></a>
            <a class="btn small secondary" href="{{ route('calendar.index', array_filter(['squadra' => $team?->id])) }}">Oggi</a>
        </div>
        @if ($teams->count() > 1)
            <form class="cal-filter" method="GET" action="{{ route('calendar.index') }}">
                <input type="hidden" name="settimana" value="{{ $start->toDateString() }}">
                <label class="sr-only" for="squadra">Squadra</label>
                <select id="squadra" name="squadra" onchange="this.form.submit()">
                    <option value="">Tutte le squadre</option>
                    @foreach ($teams as $t)
                        <option value="{{ $t->id }}" @selected($t->is($team))>{{ $t->name }}</option>
                    @endforeach
                </select>
                <noscript><button class="btn small" type="submit">Mostra</button></noscript>
            </form>
        @endif
    </div>

    @if ($teams->isEmpty() && ! auth()->user()->isAdmin())
        <div class="card empty"><x-icon name="calendar" /><p>Non hai ancora una squadra assegnata. Chiedi all'amministratore del torneo.</p></div>
    @endif

    <div class="cal-grid">
        @foreach ($days as $day)
            <section @class(['cal-day', 'today' => $day->today, 'empty-day' => $day->items->isEmpty()]) aria-label="{{ ucfirst($day->date->locale('it')->isoFormat('dddd D MMMM')) }}">
                <header class="cal-day-head">
                    <span class="cal-dow">{{ ucfirst($day->date->locale('it')->isoFormat('ddd')) }}</span>
                    <span class="cal-num">{{ $day->date->day }}</span>
                    <span class="cal-month">{{ $day->date->locale('it')->isoFormat('MMM') }}</span>
                    @if (auth()->user()->isAdmin() || $teams->isNotEmpty())
                        <a class="cal-add" href="{{ route('calendar.events.create', array_filter(['data' => $day->date->toDateString(), 'squadra' => $team?->id])) }}" title="Nuovo evento il {{ $day->date->format('d/m') }}" aria-label="Nuovo evento il {{ $day->date->format('d/m') }}"><x-icon name="plus" /></a>
                    @endif
                </header>
                <div class="cal-items">
                    @forelse ($day->items as $item)
                        <details class="cal-item ev-{{ $item->type }}">
                            <summary>
                                <span class="cal-title">{{ $item->title }}</span>
                                <span class="cal-time">{{ $item->time }}</span>
                            </summary>
                            <div class="cal-detail">
                                <span class="cal-type">{{ $item->label }}</span>
                                <dl>
                                    <div><dt><x-icon name="calendar" /><span class="sr-only">Quando</span></dt><dd>{{ ucfirst($item->date->locale('it')->isoFormat('dddd D MMMM')) }} · {{ $item->time }}</dd></div>
                                    @if ($item->place)
                                        <div><dt><x-icon name="pin" /><span class="sr-only">Luogo</span></dt><dd>{{ $item->place }}</dd></div>
                                    @endif
                                    <div><dt><x-icon name="users" /><span class="sr-only">Per</span></dt><dd>{{ $item->team }}</dd></div>
                                </dl>
                                @if ($item->notes)
                                    <p class="cal-notes">{{ $item->notes }}</p>
                                @endif
                                @if ($item->links)
                                    <div class="actions">
                                        @foreach ($item->links as [$label, $href, $icon])
                                            <a class="btn small secondary" href="{{ $href }}"><x-icon :name="$icon" /> {{ $label }}</a>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </details>
                    @empty
                        <p class="cal-none">Nessun impegno</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>

    <ul class="cal-legend" aria-label="Legenda colori">
        @foreach ($legend as $type => $label)
            <li class="ev-{{ $type }}"><span aria-hidden="true"></span>{{ $label }}</li>
        @endforeach
    </ul>
@endsection
