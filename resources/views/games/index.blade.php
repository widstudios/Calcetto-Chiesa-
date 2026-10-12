@extends('layouts.app')

@section('title', 'Calendario')

@section('content')
    <div class="page-head">
        <div>
            <span class="eyebrow">{{ $tournament?->name ?? 'Calendario' }}</span>
            <h1>Calendario partite</h1>
        </div>
        @if ($tournament)
            <a class="btn" href="{{ route('games.create', ['tournament' => $tournament->id]) }}"><x-icon name="plus" /> Nuova partita</a>
        @endif
    </div>

    @if (! $tournament)
        <div class="card empty"><x-icon name="calendar" /><p>Prima crea un torneo e le sue squadre.</p></div>
    @else
        <form class="card flush filter" method="GET" action="{{ route('games.index') }}">
            <label for="tournament">Torneo</label>
            <select id="tournament" name="tournament" onchange="this.form.submit()">
                @foreach ($tournaments as $t)
                    <option value="{{ $t->id }}" @selected($t->is($tournament))>{{ $t->name }}</option>
                @endforeach
            </select>
        </form>

        <div class="card flush">
            @if ($games->isEmpty())
                <div class="empty"><x-icon name="calendar" /><p>Nessuna partita in calendario.</p></div>
            @else
                @foreach ($games as $game)
                    @include('partials.game', ['game' => $game, 'editable' => true])
                @endforeach
            @endif
        </div>
    @endif
@endsection
