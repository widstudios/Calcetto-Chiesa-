@extends('layouts.app')

@section('title', 'Presenze')

@section('content')
    <div class="page-head">
        <div>
            <span class="eyebrow">Allenamenti</span>
            <h1>Presenze</h1>
            <p class="muted">Scegli la squadra per fare l'appello o vedere chi si allena di più.</p>
        </div>
    </div>

    <div class="card flush">
        @if ($teams->isEmpty())
            <div class="empty"><x-icon name="checklist" /><p>Nessuna squadra. {{ auth()->user()->isAdmin() ? 'Prima crea le squadre.' : 'Chiedi all\'amministratore di assegnartene una.' }}</p></div>
        @else
            <ul class="list">
                @foreach ($teams as $team)
                    <li class="list-item">
                        <span class="shirt none" aria-hidden="true"><x-icon name="users" /></span>
                        <div class="list-main">
                            <a class="list-title" href="{{ route('teams.attendances.show', $team) }}">{{ $team->name }}</a>
                            <div class="list-meta">
                                <span>{{ $team->tournament->name }}</span>
                                <span>{{ $team->players_count }} calciatori</span>
                                <span>Ultimo appello: {{ $team->attendances_max_date ? \Illuminate\Support\Carbon::parse($team->attendances_max_date)->format('d/m/Y') : 'mai' }}</span>
                            </div>
                        </div>
                        <div class="actions">
                            <a class="btn small" href="{{ route('teams.attendances.show', $team) }}"><x-icon name="checklist" /> <span class="btn-text">Appello</span></a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
