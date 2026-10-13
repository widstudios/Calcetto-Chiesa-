@extends('layouts.app')

@section('title', 'Pannello amministratore')

@section('content')
    <div class="page-head">
        <div>
            <span class="eyebrow">Ciao {{ auth()->user()->name }}</span>
            <h1>Pannello amministratore</h1>
        </div>
    </div>

    <div class="stats">
        <div class="stat">
            <span class="stat-icon gold"><x-icon name="trophy" /></span>
            <div><div class="stat-value">{{ $tournaments->count() }}</div><div class="stat-label">Tornei</div></div>
        </div>
        <div class="stat">
            <span class="stat-icon"><x-icon name="users" /></span>
            <div><div class="stat-value">{{ $tournaments->sum('teams_count') }}</div><div class="stat-label">Squadre</div></div>
        </div>
        <div class="stat">
            <span class="stat-icon"><x-icon name="calendar" /></span>
            <div><div class="stat-value">{{ $tournaments->sum('games_count') }}</div><div class="stat-label">Partite</div></div>
        </div>
    </div>

    <section class="card">
        <h2>Inserisci i dati</h2>
        <div class="quick">
            <a href="{{ route('tournaments.create') }}"><x-icon name="trophy" /> Nuovo torneo</a>
            <a href="{{ route('teams.create') }}"><x-icon name="users" /> Nuova squadra</a>
            <a href="{{ route('coaches.create') }}"><x-icon name="coach" /> Nuovo allenatore</a>
            <a href="{{ route('games.index') }}"><x-icon name="calendar" /> Calendario partite</a>
            <a href="{{ route('imports.index') }}"><x-icon name="upload" /> Importa da Excel</a>
        </div>
        <p class="hint">I calciatori si aggiungono dalla pagina della squadra (Squadre → apri la rosa).</p>
    </section>

    <section class="card flush">
        <div class="card-head">
            <h2>Prossime partite</h2>
            <a class="small" href="{{ route('games.index') }}">Tutto il calendario</a>
        </div>
        @if ($upcomingGames->isEmpty())
            <div class="empty"><x-icon name="calendar" /><p>Nessuna partita in programma.</p></div>
        @else
            @foreach ($upcomingGames as $game)
                @include('partials.game', ['game' => $game])
            @endforeach
        @endif
    </section>

    <section class="card flush">
        <div class="card-head">
            <h2>Tornei</h2>
            <a class="small" href="{{ route('tournaments.index') }}">Gestisci</a>
        </div>
        @if ($tournaments->isEmpty())
            <div class="empty"><x-icon name="trophy" /><p>Nessun torneo ancora.</p></div>
        @else
            <ul class="list">
                @foreach ($tournaments as $tournament)
                    <li class="list-item">
                        <span class="avatar gold"><x-icon name="trophy" /></span>
                        <div class="list-main">
                            <a class="list-title" href="{{ route('teams.index', ['tournament' => $tournament->id]) }}">{{ $tournament->name }}</a>
                            <div class="list-meta">
                                @if ($tournament->season)<span>{{ $tournament->season }}</span>@endif
                                <span>{{ $tournament->teams_count }} squadre</span>
                                <span>{{ $tournament->games_count }} partite</span>
                            </div>
                        </div>
                        <x-icon name="chevron" class="muted" />
                    </li>
                @endforeach
            </ul>
        @endif
    </section>
@endsection
