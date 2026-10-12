@extends('layouts.app')

@section('title', 'Calendario')

@section('content')
    <div class="toolbar">
        <h1>Calendario partite</h1>
        @if ($tournament)
            <a class="btn small" href="{{ route('games.create', ['tournament' => $tournament->id]) }}">+ Nuova partita</a>
        @endif
    </div>

    @if (! $tournament)
        <div class="card"><p class="muted">Prima crea un torneo e le sue squadre.</p></div>
    @else
        <form class="card" method="GET" action="{{ route('games.index') }}">
            <label for="tournament" style="margin-top: 0">Torneo</label>
            <select id="tournament" name="tournament" onchange="this.form.submit()">
                @foreach ($tournaments as $t)
                    <option value="{{ $t->id }}" @selected($t->is($tournament))>{{ $t->name }}</option>
                @endforeach
            </select>
        </form>

        <div class="card table-wrap">
            @if ($games->isEmpty())
                <p class="muted">Nessuna partita in calendario.</p>
            @else
                <table>
                    <thead><tr><th>Giornata</th><th>Data</th><th>Partita</th><th>Risultato</th><th>Campo</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($games as $game)
                        <tr>
                            <td>{{ $game->matchday }}</td>
                            <td>{{ $game->kickoff_at?->format('d/m/Y H:i') }}</td>
                            <td><a href="{{ route('games.edit', $game) }}">{{ $game->homeTeam->name }} – {{ $game->awayTeam->name }}</a></td>
                            <td>
                                @if ($game->isPlayed())
                                    <strong>{{ $game->home_score }} – {{ $game->away_score }}</strong>
                                @else
                                    <span class="muted">{{ $game->status->label() }}</span>
                                @endif
                            </td>
                            <td>{{ $game->venue }}</td>
                            <td class="actions">
                                <a class="btn small secondary" href="{{ route('games.edit', $game) }}">Modifica</a>
                                @include('partials.delete', ['action' => route('games.destroy', $game), 'confirm' => 'Eliminare questa partita?'])
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endif
@endsection
