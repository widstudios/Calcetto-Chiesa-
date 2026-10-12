@extends('layouts.app')

@section('title', $team->name)

@section('content')
    <div class="toolbar">
        <div>
            <h1>{{ $team->name }}</h1>
            <p class="muted" style="margin: 4px 0 0">{{ $team->tournament->name }} · Allenatore: {{ $team->coachDisplayName() ?? 'non indicato' }}</p>
        </div>
        <div class="actions">
            @can('manageRoster', $team)
                <a class="btn small" href="{{ route('teams.players.create', $team) }}">+ Aggiungi calciatore</a>
            @endcan
            @can('update', $team)
                <a class="btn small secondary" href="{{ route('teams.edit', $team) }}">Modifica squadra</a>
            @endcan
            @can('delete', $team)
                @include('partials.delete', ['action' => route('teams.destroy', $team), 'confirm' => 'Eliminare la squadra e la sua rosa?'])
            @endcan
        </div>
    </div>

    @foreach (App\Enums\SquadRole::cases() as $role)
        @php($players = $team->players->where('squad_role', $role))
        <div class="card table-wrap">
            <h2 style="margin-top: 0">{{ $role->label() }} <span class="muted">({{ $players->count() }})</span></h2>
            @if ($players->isEmpty())
                <p class="muted">Nessun calciatore.</p>
            @else
                <table>
                    <thead><tr><th>N.</th><th>Calciatore</th><th>Anno di nascita</th><th></th></tr></thead>
                    <tbody>
                    @foreach ($players as $player)
                        <tr>
                            <td>{{ $player->shirt_number }}</td>
                            <td>{{ $player->fullName() }}</td>
                            <td>{{ $player->birth_year }}</td>
                            <td class="actions">
                                @can('manageRoster', $team)
                                    <a class="btn small secondary" href="{{ route('teams.players.edit', [$team, $player]) }}">Modifica</a>
                                    @include('partials.delete', ['action' => route('teams.players.destroy', [$team, $player]), 'confirm' => 'Togliere '.$player->fullName().' dalla rosa?'])
                                @endcan
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            @endif
        </div>
    @endforeach
@endsection
