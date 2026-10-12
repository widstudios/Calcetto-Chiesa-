@extends('layouts.app')

@section('title', auth()->user()->isAdmin() ? 'Squadre' : 'La mia rosa')

@section('content')
    <div class="page-head">
        <div>
            <span class="eyebrow">{{ auth()->user()->isAdmin() ? 'Squadre e rose' : 'La mia rosa' }}</span>
            <h1>{{ auth()->user()->isAdmin() ? 'Squadre' : 'Le mie squadre' }}</h1>
        </div>
        @can('create', App\Models\Team::class)
            <a class="btn" href="{{ route('teams.create', ['tournament' => request('tournament')]) }}"><x-icon name="plus" /> Nuova squadra</a>
        @endcan
    </div>

    @if ($tournaments->isNotEmpty())
        <form class="card flush filter" method="GET" action="{{ route('teams.index') }}">
            <label for="tournament">Torneo</label>
            <select id="tournament" name="tournament" onchange="this.form.submit()">
                <option value="">Tutti i tornei</option>
                @foreach ($tournaments as $t)
                    <option value="{{ $t->id }}" @selected(request('tournament') == $t->id)>{{ $t->name }}</option>
                @endforeach
            </select>
        </form>
    @endif

    <div class="card flush">
        @if ($teams->isEmpty())
            <div class="empty">
                <x-icon name="users" />
                <p>
                    @if (auth()->user()->isAdmin())
                        Nessuna squadra. Prima crea un torneo, poi aggiungi le squadre.
                    @else
                        Non hai ancora una squadra assegnata. Chiedi all'amministratore del torneo.
                    @endif
                </p>
            </div>
        @else
            <ul class="list">
                @foreach ($teams as $team)
                    <li class="list-item">
                        <span class="avatar">{{ mb_substr($team->name, 0, 2) }}</span>
                        <div class="list-main">
                            <a class="list-title" href="{{ route('teams.show', $team) }}">{{ $team->name }}</a>
                            <div class="list-meta">
                                <span><x-icon name="trophy" /> {{ $team->tournament->name }}</span>
                                <span><x-icon name="coach" /> {{ $team->coachDisplayName() ?? 'Allenatore non indicato' }}</span>
                                <span><x-icon name="shirt" /> {{ $team->players_count }} calciatori</span>
                            </div>
                        </div>
                        <div class="actions">
                            <a class="btn small secondary" href="{{ route('teams.show', $team) }}"><span class="btn-text">Apri rosa</span> <x-icon name="chevron" /></a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
