@extends('layouts.app')

@section('title', 'Distinte')

@section('content')
    <div class="page-head">
        <div>
            <span class="eyebrow">Gare</span>
            <h1>Distinte</h1>
            <p class="muted">Scegli la squadra: nella distinta decidi chi va in campo, con numeri di maglia e capitano, anche senza una partita in calendario.</p>
        </div>
    </div>

    <div class="card flush">
        @if ($teams->isEmpty())
            <div class="empty"><x-icon name="clipboard" /><p>Nessuna squadra. {{ auth()->user()->isAdmin() ? 'Prima crea le squadre.' : 'Chiedi all\'amministratore di assegnartene una.' }}</p></div>
        @else
            <ul class="list">
                @foreach ($teams as $team)
                    <li class="list-item">
                        <span class="shirt none" aria-hidden="true"><x-icon name="users" /></span>
                        <div class="list-main">
                            <a class="list-title" href="{{ route('teams.lineups.index', $team) }}">{{ $team->name }}</a>
                            <div class="list-meta">
                                <span>{{ $team->tournament->name }}</span>
                                <span>{{ $team->players_count }} calciatori</span>
                                <span>{{ $team->lineups_count }} {{ $team->lineups_count === 1 ? 'distinta' : 'distinte' }}</span>
                            </div>
                        </div>
                        <div class="actions">
                            <a class="btn small" href="{{ route('teams.lineups.index', $team) }}"><x-icon name="clipboard" /> <span class="btn-text">Distinte</span></a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
