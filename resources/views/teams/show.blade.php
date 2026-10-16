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
                <a class="btn secondary" href="{{ route('teams.attendances.show', $team) }}"><x-icon name="checklist" /> Presenze</a>
                <a class="btn secondary" href="{{ route('teams.lineups.index', $team) }}"><x-icon name="clipboard" /> Distinte</a>
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

    <section class="card flush">
        <div class="card-head">
            <h2>Allenamenti</h2>
            <span class="badge">{{ $team->trainings->count() }} a settimana</span>
        </div>
        @if ($team->trainings->isEmpty())
            <div class="empty"><x-icon name="clock" /><p>Nessun orario di allenamento.</p></div>
        @else
            <ul class="list" style="margin-top: 8px">
                @foreach ($team->trainings as $training)
                    <li class="list-item">
                        <span class="shirt none" aria-hidden="true"><x-icon name="calendar" /></span>
                        <div class="list-main">
                            <div class="list-title">{{ $training->weekdayName() }} · {{ $training->timeRange() }}</div>
                            <div class="list-meta"><span>Campo: {{ $training->field }}</span></div>
                        </div>
                        @can('manageRoster', $team)
                            <div class="actions">
                                @include('partials.delete', ['action' => route('teams.trainings.destroy', [$team, $training]), 'confirm' => 'Togliere l\'allenamento del '.$training->weekdayName().'?', 'label' => 'Togli'])
                            </div>
                        @endcan
                    </li>
                @endforeach
            </ul>
        @endif
        @can('manageRoster', $team)
            <form method="POST" action="{{ route('teams.trainings.store', $team) }}" style="padding: 0 16px 16px">
                @csrf
                <div class="grid">
                    <div>
                        <label for="weekday">Giorno</label>
                        <select id="weekday" name="weekday" required>
                            @foreach (App\Models\Training::WEEKDAYS as $number => $day)
                                <option value="{{ $number }}" @selected(old('weekday') == $number)>{{ $day }}</option>
                            @endforeach
                        </select>
                        @include('partials.error', ['field' => 'weekday'])
                    </div>
                    <div>
                        <label for="field">Campo</label>
                        <input id="field" type="text" name="field" value="{{ old('field') }}" required>
                        @include('partials.error', ['field' => 'field'])
                    </div>
                    <div>
                        <label for="starts_at">Dalle</label>
                        <input id="starts_at" type="time" name="starts_at" value="{{ old('starts_at') }}" required>
                        @include('partials.error', ['field' => 'starts_at'])
                    </div>
                    <div>
                        <label for="ends_at">Alle</label>
                        <input id="ends_at" type="time" name="ends_at" value="{{ old('ends_at') }}" required>
                        @include('partials.error', ['field' => 'ends_at'])
                    </div>
                </div>
                <div class="form-actions">
                    <button class="btn" type="submit"><x-icon name="plus" /> Aggiungi allenamento</button>
                </div>
            </form>
        @endcan
    </section>
@endsection
