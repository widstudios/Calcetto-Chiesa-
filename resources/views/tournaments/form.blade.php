@extends('layouts.app')

@section('title', $tournament->exists ? 'Modifica torneo' : 'Nuovo torneo')

@section('content')
    @include('partials.form-head', ['back' => route('tournaments.index'), 'title' => $tournament->exists ? 'Modifica torneo' : 'Nuovo torneo'])

    <form class="card form-card" method="POST" action="{{ $tournament->exists ? route('tournaments.update', $tournament) : route('tournaments.store') }}">
        @csrf
        @if ($tournament->exists) @method('PUT') @endif

        <label for="name" style="margin-top: 0">Nome del torneo *</label>
        <input id="name" type="text" name="name" value="{{ old('name', $tournament->name) }}" required @error('name') aria-invalid="true" @enderror>
        @include('partials.error', ['field' => 'name'])

        <div class="grid">
            <div>
                <label for="season">Stagione</label>
                <input id="season" type="text" name="season" placeholder="2026/2027" value="{{ old('season', $tournament->season) }}">
                @include('partials.error', ['field' => 'season'])
            </div>
            <div>
                <label for="starts_on">Inizio</label>
                <input id="starts_on" type="date" name="starts_on" value="{{ old('starts_on', $tournament->starts_on?->format('Y-m-d')) }}">
                @include('partials.error', ['field' => 'starts_on'])
            </div>
            <div>
                <label for="ends_on">Fine</label>
                <input id="ends_on" type="date" name="ends_on" value="{{ old('ends_on', $tournament->ends_on?->format('Y-m-d')) }}">
                @include('partials.error', ['field' => 'ends_on'])
            </div>
        </div>

        <label for="lineup_max_players">Giocatori in distinta al massimo</label>
        <input id="lineup_max_players" type="number" inputmode="numeric" min="1" max="40" name="lineup_max_players" value="{{ old('lineup_max_players', $tournament->lineup_max_players) }}" placeholder="{{ App\Models\Setting::lineupMaxPlayers() }}" style="max-width: 160px" @error('lineup_max_players') aria-invalid="true" @enderror>
        <p class="hint">Lascia vuoto per usare il valore delle Impostazioni ({{ App\Models\Setting::lineupMaxPlayers() }}, dal regolamento). Mettilo solo se questa categoria ha un limite diverso.</p>
        @include('partials.error', ['field' => 'lineup_max_players'])

        <label for="description">Descrizione</label>
        <textarea id="description" name="description" rows="3">{{ old('description', $tournament->description) }}</textarea>

        <div class="form-actions">
            <button class="btn" type="submit">Salva</button>
            <a class="btn secondary" href="{{ route('tournaments.index') }}">Annulla</a>
        </div>
    </form>
@endsection
