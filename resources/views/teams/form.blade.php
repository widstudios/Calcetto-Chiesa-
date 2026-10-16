@extends('layouts.app')

@section('title', $team->exists ? 'Modifica squadra' : 'Nuova squadra')

@section('content')
    @include('partials.form-head', ['back' => $team->exists ? route('teams.show', $team) : route('teams.index'), 'title' => $team->exists ? 'Modifica squadra' : 'Nuova squadra'])

    @if ($tournaments->isEmpty())
        <div class="card empty">
            <x-icon name="trophy" />
            <p>Prima di creare una squadra serve un torneo.</p>
            <a class="btn" href="{{ route('tournaments.create') }}"><x-icon name="plus" /> Crea un torneo</a>
        </div>
    @else
        <form class="card form-card" method="POST" action="{{ $team->exists ? route('teams.update', $team) : route('teams.store') }}">
            @csrf
            @if ($team->exists) @method('PUT') @endif

            <label for="tournament_id" style="margin-top: 0">Torneo *</label>
            <select id="tournament_id" name="tournament_id" required>
                @foreach ($tournaments as $t)
                    <option value="{{ $t->id }}" @selected(old('tournament_id', $team->tournament_id) == $t->id)>{{ $t->name }}</option>
                @endforeach
            </select>
            @include('partials.error', ['field' => 'tournament_id'])

            <label for="name">Nome della squadra *</label>
            <input id="name" type="text" name="name" value="{{ old('name', $team->name) }}" required @error('name') aria-invalid="true" @enderror>
            @include('partials.error', ['field' => 'name'])

            <label for="coach_name">Nome dell'allenatore</label>
            <input id="coach_name" type="text" name="coach_name" value="{{ old('coach_name', $team->coach_name) }}">
            @include('partials.error', ['field' => 'coach_name'])

            <span class="legend">Account degli allenatori (facoltativo, anche più d'uno)</span>
            @php($selectedCoaches = array_map('intval', old('coaches', $team->exists ? $team->coaches->pluck('id')->all() : [])))
            @forelse ($coaches as $coach)
                <label class="check">
                    <input type="checkbox" name="coaches[]" value="{{ $coach->id }}" @checked(in_array($coach->id, $selectedCoaches))>
                    <span>{{ $coach->name }} <span class="muted">({{ $coach->email }})</span></span>
                </label>
            @empty
                <p class="muted">Nessun account allenatore.</p>
            @endforelse
            <p class="hint">Chi ha un account può entrare e gestire da solo la rosa. Gli account si creano in <a href="{{ route('coaches.index') }}">Allenatori</a>.</p>
            @include('partials.error', ['field' => 'coaches'])

            <div class="form-actions">
                <button class="btn" type="submit">Salva</button>
                <a class="btn secondary" href="{{ $team->exists ? route('teams.show', $team) : route('teams.index') }}">Annulla</a>
            </div>
        </form>
    @endif
@endsection
