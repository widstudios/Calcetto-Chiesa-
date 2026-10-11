@extends('layouts.app')

@section('title', 'Pannello amministratore')

@section('content')
    <h1>Pannello amministratore</h1>

    <div class="card">
        <h2>Tornei</h2>
        @forelse ($tournaments as $tournament)
            <p>
                <strong>{{ $tournament->name }}</strong>
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
