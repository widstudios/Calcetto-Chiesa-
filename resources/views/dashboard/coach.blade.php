@extends('layouts.app')

@section('title', 'La mia squadra')

@section('content')
    <div class="page-head">
        <div>
            <span class="eyebrow">Ciao {{ auth()->user()->name }}</span>
            <h1>La mia squadra</h1>
        </div>
    </div>

    @forelse ($teams as $team)
        <section class="card flush">
            <div class="card-head">
                <div class="team-head">
                    <x-team-badge :team="$team" />
                    <div>
                        <h2>{{ $team->name }}</h2>
                        <p class="muted small" style="margin: 2px 0 0">{{ $team->tournament->name }} · Allenatore: {{ $team->coachDisplayName() }}</p>
                    </div>
                </div>
                <div class="actions">
                    <a class="btn small" href="{{ route('teams.players.create', $team) }}"><x-icon name="plus" /> Calciatore</a>
                    <a class="btn small secondary" href="{{ route('teams.show', $team) }}">Gestisci rosa</a>
                    <a class="btn small secondary" href="{{ route('teams.attendances.show', $team) }}"><x-icon name="checklist" /> Appello</a>
                    <a class="btn small secondary" href="{{ route('drills.index') }}"><x-icon name="cone" /> Esercizi</a>
                </div>
            </div>
            @if ($team->players->isEmpty())
                <div class="empty"><x-icon name="shirt" /><p>Nessun calciatore in rosa.</p></div>
            @else
                <div style="margin-top: 8px">
                    @include('partials.roster', ['players' => $team->players, 'team' => $team, 'showRole' => true])
                </div>
            @endif
        </section>
    @empty
        <div class="card empty"><x-icon name="shirt" /><p>Non hai ancora una squadra assegnata. Chiedi all'amministratore del torneo.</p></div>
    @endforelse
@endsection
