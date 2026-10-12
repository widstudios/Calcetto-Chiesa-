@extends('layouts.app')

@section('title', 'Pannello amministratore')

@section('content')
    <h1>Pannello amministratore</h1>

    <div class="card">
        <h2 style="margin-top: 0">Inserisci i dati</h2>
        <div class="actions">
            <a class="btn small" href="{{ route('tournaments.create') }}">+ Torneo</a>
            <a class="btn small" href="{{ route('teams.create') }}">+ Squadra</a>
            <a class="btn small" href="{{ route('coaches.create') }}">+ Allenatore</a>
            <a class="btn small" href="{{ route('games.index') }}">Calendario partite</a>
        </div>
        <p class="muted" style="margin-bottom: 0">I calciatori si aggiungono dalla pagina della squadra (Squadre e rose → Apri rosa).</p>
    </div>

    <div class="card">
        <h2>Tornei</h2>
        @forelse ($tournaments as $tournament)
            <p>
                <a href="{{ route('teams.index', ['tournament' => $tournament->id]) }}"><strong>{{ $tournament->name }}</strong></a>
                <span class="muted">{{ $tournament->season }} · {{ $tournament->teams_count }} squadre · {{ $tournament->games_count }} partite</span>
            </p>
        @empty
            <p class="muted">Nessun torneo ancora.</p>
        @endforelse
    </div>

    <div class="card">
        <h2>Prossime partite</h2>
        @if ($upcomingGames->isEmpty())
            <p class="muted">Nessuna partita in programma.</p>
        @else
            <table>
                <thead><tr><th>Data</th><th>Partita</th><th>Campo</th></tr></thead>
                <tbody>
                @foreach ($upcomingGames as $game)
                    <tr>
                        <td>{{ $game->kickoff_at?->format('d/m/Y H:i') }}</td>
                        <td>{{ $game->homeTeam->name }} – {{ $game->awayTeam->name }}</td>
                        <td>{{ $game->venue }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
