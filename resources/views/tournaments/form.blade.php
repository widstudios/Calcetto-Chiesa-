@extends('layouts.app')

@section('title', $tournament->exists ? 'Modifica torneo' : 'Nuovo torneo')

@section('content')
    <h1>{{ $tournament->exists ? 'Modifica torneo' : 'Nuovo torneo' }}</h1>

    <form class="card" method="POST" action="{{ $tournament->exists ? route('tournaments.update', $tournament) : route('tournaments.store') }}">
        @csrf
        @if ($tournament->exists) @method('PUT') @endif

        <label for="name">Nome del torneo *</label>
        <input id="name" type="text" name="name" value="{{ old('name', $tournament->name) }}" required>
        @error('name') <div class="error">{{ $message }}</div> @enderror

        <div class="grid">
            <div>
                <label for="season">Stagione</label>
                <input id="season" type="text" name="season" placeholder="2026/2027" value="{{ old('season', $tournament->season) }}">
                @error('season') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="starts_on">Inizio</label>
                <input id="starts_on" type="date" name="starts_on" value="{{ old('starts_on', $tournament->starts_on?->format('Y-m-d')) }}">
                @error('starts_on') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="ends_on">Fine</label>
                <input id="ends_on" type="date" name="ends_on" value="{{ old('ends_on', $tournament->ends_on?->format('Y-m-d')) }}">
                @error('ends_on') <div class="error">{{ $message }}</div> @enderror
            </div>
        </div>

        <label for="description">Descrizione</label>
        <textarea id="description" name="description" rows="3">{{ old('description', $tournament->description) }}</textarea>

        <button class="btn" type="submit">Salva</button>
        <a class="btn secondary" href="{{ route('tournaments.index') }}">Annulla</a>
    </form>
@endsection
