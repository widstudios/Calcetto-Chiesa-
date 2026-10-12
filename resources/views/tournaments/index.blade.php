@extends('layouts.app')

@section('title', 'Tornei')

@section('content')
    <div class="page-head">
        <div>
            <span class="eyebrow">Gestione</span>
            <h1>Tornei</h1>
        </div>
        <a class="btn" href="{{ route('tournaments.create') }}"><x-icon name="plus" /> Nuovo torneo</a>
    </div>

    <div class="card flush table-wrap">
        @if ($tournaments->isEmpty())
            <div class="empty"><x-icon name="trophy" /><p>Nessun torneo. Crea il primo con "Nuovo torneo".</p></div>
        @else
            <table class="stack">
                <thead><tr><th>Nome</th><th>Stagione</th><th>Date</th><th>Squadre</th><th>Partite</th><th><span class="sr-only">Azioni</span></th></tr></thead>
                <tbody>
                @foreach ($tournaments as $tournament)
                    <tr>
                        <td class="title"><strong>{{ $tournament->name }}</strong></td>
                        <td data-label="Stagione">{{ $tournament->season ?: '—' }}</td>
                        <td data-label="Date">
                            @if ($tournament->starts_on || $tournament->ends_on)
                                {{ $tournament->starts_on?->format('d/m/Y') }} – {{ $tournament->ends_on?->format('d/m/Y') }}
                            @else
                                —
                            @endif
                        </td>
                        <td data-label="Squadre" class="num"><a href="{{ route('teams.index', ['tournament' => $tournament->id]) }}">{{ $tournament->teams_count }}</a></td>
                        <td data-label="Partite" class="num"><a href="{{ route('games.index', ['tournament' => $tournament->id]) }}">{{ $tournament->games_count }}</a></td>
                        <td class="actions">
                            <a class="btn small secondary" href="{{ route('tournaments.edit', $tournament) }}"><x-icon name="pencil" /> <span class="btn-text">Modifica</span></a>
                            @include('partials.delete', ['action' => route('tournaments.destroy', $tournament), 'confirm' => 'Eliminare il torneo con tutte le sue squadre e partite?'])
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
