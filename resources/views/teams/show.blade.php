@extends('layouts.app')

@section('title', $team->name)

@section('content')
    <div class="page-head">
        <div>
            <span class="eyebrow">{{ $team->tournament->name }}</span>
            <h1>{{ $team->name }}</h1>
            <p class="muted">Allenatore: {{ $team->coachDisplayName() ?? 'non indicato' }}</p>
        </div>
        <div class="actions">
            @can('manageRoster', $team)
                <a class="btn" href="{{ route('teams.players.create', $team) }}"><x-icon name="plus" /> Aggiungi calciatore</a>
            @endcan
            @can('update', $team)
                <a class="btn secondary" href="{{ route('teams.edit', $team) }}"><x-icon name="pencil" /> Modifica squadra</a>
            @endcan
            @can('delete', $team)
                @include('partials.delete', ['action' => route('teams.destroy', $team), 'confirm' => 'Eliminare la squadra e la sua rosa?', 'label' => 'Elimina squadra'])
            @endcan
        </div>
    </div>

    @foreach (App\Enums\SquadRole::cases() as $role)
        @php($players = $team->players->where('squad_role', $role))
        <section class="card flush">
            <div class="card-head">
                <h2>{{ $role->label() }}</h2>
                <span @class(['badge', 'bench' => $role === App\Enums\SquadRole::Panchina])>{{ $players->count() }} calciatori</span>
            </div>
            @if ($players->isEmpty())
                <div class="empty"><x-icon name="shirt" /><p>Nessun calciatore.</p></div>
            @else
                <div style="margin-top: 8px">
                    @include('partials.roster', ['players' => $players, 'team' => $team, 'editable' => auth()->user()->can('manageRoster', $team)])
                </div>
            @endif
        </section>
    @endforeach
@endsection
