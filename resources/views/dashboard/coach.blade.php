@extends('layouts.app')

@section('title', 'La mia squadra')

@section('content')
    <h1>La mia squadra</h1>

    @forelse ($teams as $team)
        <div class="card">
            <h2>{{ $team->name }}</h2>
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
