@extends('layouts.app')

@section('title', auth()->user()->isAdmin() ? 'Squadre' : 'La mia rosa')

@section('content')
    <div class="toolbar">
        <h1>{{ auth()->user()->isAdmin() ? 'Squadre' : 'Le mie squadre' }}</h1>
        @can('create', App\Models\Team::class)
            <a class="btn small" href="{{ route('teams.create', ['tournament' => request('tournament')]) }}">+ Nuova squadra</a>
        @endcan
    </div>

    @if ($tournaments->isNotEmpty())
        <form class="card" method="GET" action="{{ route('teams.index') }}">
            <label for="tournament" style="margin-top: 0">Torneo</label>
            <select id="tournament" name="tournament" onchange="this.form.submit()">
                <option value="">Tutti i tornei</option>
                @foreach ($tournaments as $t)
                    <option value="{{ $t->id }}" @selected(request('tournament') == $t->id)>{{ $t->name }}</option>
                @endforeach
            </select>
        </form>
    @endif

    <div class="card table-wrap">
        @if ($teams->isEmpty())
            <p class="muted">
                @if (auth()->user()->isAdmin())
                    Nessuna squadra. Prima crea un torneo, poi aggiungi le squadre.
                @else
                    Non hai ancora una squadra assegnata. Chiedi all'amministratore del torneo.
                @endif
            </p>
        @else
            <table>
                <thead><tr><th>Squadra</th><th>Torneo</th><th>Allenatore</th><th>Calciatori</th><th></th></tr></thead>
                <tbody>
                @foreach ($teams as $team)
                    <tr>
                        <td><a href="{{ route('teams.show', $team) }}"><strong>{{ $team->name }}</strong></a></td>
                        <td>{{ $team->tournament->name }}</td>
                        <td>{{ $team->coachDisplayName() ?? '—' }}</td>
                        <td>{{ $team->players_count }}</td>
                        <td class="actions"><a class="btn small secondary" href="{{ route('teams.show', $team) }}">Apri rosa</a></td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
@endsection
