@extends('layouts.app')

@section('title', $team->exists ? 'Modifica squadra' : 'Nuova squadra')

@section('content')
    <h1>{{ $team->exists ? 'Modifica squadra' : 'Nuova squadra' }}</h1>

    @if ($tournaments->isEmpty())
        <div class="card">
            <p>Prima di creare una squadra serve un torneo.</p>
            <a class="btn" href="{{ route('tournaments.create') }}">Crea un torneo</a>
        </div>
    @else
        <form class="card" method="POST" action="{{ $team->exists ? route('teams.update', $team) : route('teams.store') }}">
            @csrf
            @if ($team->exists) @method('PUT') @endif

            <label for="tournament_id">Torneo *</label>
            <select id="tournament_id" name="tournament_id" required>
                @foreach ($tournaments as $t)
                    <option value="{{ $t->id }}" @selected(old('tournament_id', $team->tournament_id) == $t->id)>{{ $t->name }}</option>
                @endforeach
            </select>
            @error('tournament_id') <div class="error">{{ $message }}</div> @enderror

            <label for="name">Nome della squadra *</label>
            <input id="name" type="text" name="name" value="{{ old('name', $team->name) }}" required>
            @error('name') <div class="error">{{ $message }}</div> @enderror

            <label for="coach_name">Nome dell'allenatore</label>
            <input id="coach_name" type="text" name="coach_name" value="{{ old('coach_name', $team->coach_name) }}">
            @error('coach_name') <div class="error">{{ $message }}</div> @enderror

            <label for="coach_id">Account dell'allenatore (facoltativo)</label>
            <select id="coach_id" name="coach_id">
                <option value="">Nessuno</option>
                @foreach ($coaches as $coach)
                    <option value="{{ $coach->id }}" @selected(old('coach_id', $team->coach_id) == $coach->id)>{{ $coach->name }} ({{ $coach->email }})</option>
                @endforeach
            </select>
            <p class="muted" style="font-size: 14px">Se l'allenatore ha un account, potrà entrare e gestire da solo la rosa. Gli account si creano in <a href="{{ route('coaches.index') }}">Allenatori</a>.</p>
            @error('coach_id') <div class="error">{{ $message }}</div> @enderror

            <button class="btn" type="submit">Salva</button>
            <a class="btn secondary" href="{{ $team->exists ? route('teams.show', $team) : route('teams.index') }}">Annulla</a>
        </form>
    @endif
@endsection
