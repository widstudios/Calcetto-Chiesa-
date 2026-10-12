@extends('layouts.app')

@section('title', 'La mia squadra')

@section('content')
    <h1>La mia squadra</h1>

    @forelse ($teams as $team)
        <div class="card">
            <div class="toolbar">
                <h2 style="margin: 0">{{ $team->name }}</h2>
                <div class="actions">
                    <a class="btn small" href="{{ route('teams.players.create', $team) }}">+ Aggiungi calciatore</a>
                    <a class="btn small secondary" href="{{ route('teams.show', $team) }}">Gestisci rosa</a>
                </div>
            </div>
            <p class="muted">{{ $team->tournament->name }} · Allenatore: {{ $team->coachDisplayName() }}</p>
            <table>
                <thead><tr><th>N.</th><th>Calciatore</th><th>Anno di nascita</th><th>Ruolo in rosa</th></tr></thead>
                <tbody>
                @foreach ($team->players as $player)
                    <tr>
                        <td>{{ $player->shirt_number }}</td>
                        <td>{{ $player->fullName() }}</td>
                        <td>{{ $player->birth_year }}</td>
                        <td>
                            <span @class(['badge', 'bench' => $player->squad_role === \App\Enums\SquadRole::Panchina])>
                                {{ $player->squad_role->label() }}
                            </span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <div class="card"><p class="muted">Non hai ancora una squadra assegnata. Chiedi all'amministratore del torneo.</p></div>
    @endforelse
@endsection
