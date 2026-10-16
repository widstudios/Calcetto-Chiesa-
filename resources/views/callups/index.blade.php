@extends('layouts.app')

@section('title', 'Distinte')

@section('content')
    <div class="page-head">
        <div>
            <span class="eyebrow">{{ $tournament?->name ?? 'Partite' }}</span>
            <h1>Distinte di gara</h1>
            <p class="muted">Per ogni partita scegli i convocati con i numeri di maglia, poi stampa la distinta.</p>
        </div>
    </div>

    @if ($tournaments->count() > 1)
        <form class="card flush filter" method="GET" action="{{ route('callups.index') }}">
            <label for="tournament">Torneo</label>
            <select id="tournament" name="tournament" onchange="this.form.submit()">
                @foreach ($tournaments as $t)
                    <option value="{{ $t->id }}" @selected($t->is($tournament))>{{ $t->name }}</option>
                @endforeach
            </select>
        </form>
    @endif

    <div class="card flush">
        @if ($games->isEmpty())
            <div class="empty"><x-icon name="clipboard" /><p>Nessuna partita in programma.</p></div>
        @else
            @foreach ($games as $game)
                <div class="callup-game">
                    @include('partials.game', ['game' => $game])
                    <div class="game-bottom">
                        <div class="actions">
                            @foreach (['home' => $game->homeTeam, 'away' => $game->awayTeam] as $side => $team)
                                @continue($myTeams && ! $myTeams->contains($team->id))
                                @php($count = $game->{$side.'_callups_count'})
                                @if ($count)
                                    <a class="btn small secondary" href="{{ route('games.callups.edit', [$game, $team]) }}"><x-icon name="pencil" /> {{ $team->name }} ({{ $count }})</a>
                                    <a class="btn small secondary" href="{{ route('games.callups.print', [$game, $team]) }}"><x-icon name="printer" /> Stampa</a>
                                @else
                                    <a class="btn small" href="{{ route('games.callups.edit', [$game, $team]) }}"><x-icon name="clipboard" /> Distinta {{ $team->name }}</a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                </div>
            @endforeach
        @endif
    </div>
@endsection
