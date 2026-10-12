@extends('layouts.app')

@section('title', 'Tornei')

@section('content')
    <div class="toolbar">
        <h1>Tornei</h1>
        <a class="btn small" href="{{ route('tournaments.create') }}">+ Nuovo torneo</a>
    </div>

    <div class="card table-wrap">
        @if ($tournaments->isEmpty())
            <p class="muted">Nessun torneo. Crea il primo con "Nuovo torneo".</p>
        @else
            <table>
                <thead><tr><th>Nome</th><th>Stagione</th><th>Date</th><th>Squadre</th><th>Partite</th><th></th></tr></thead>
                <tbody>
                @foreach ($tournaments as $tournament)
                    <tr>
                        <td><strong>{{ $tournament->name }}</strong></td>
                        <td>{{ $tournament->season }}</td>
                        <td>{{ $tournament->starts_on?->format('d/m/Y') }} – {{ $tournament->ends_on?->format('d/m/Y') }}</td>
                        <td><a href="{{ route('teams.index', ['tournament' => $tournament->id]) }}">{{ $tournament->teams_count }}</a></td>
                        <td><a href="{{ route('games.index', ['tournament' => $tournament->id]) }}">{{ $tournament->games_count }}</a></td>
                        <td class="actions">
                            <a class="btn small secondary" href="{{ route('tournaments.edit', $tournament) }}">Modifica</a>
                            @include('partials.delete', ['action' => route('tournaments.destroy', $tournament), 'confirm' => 'Eliminare il torneo con tutte le sue squadre e partite?'])
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
