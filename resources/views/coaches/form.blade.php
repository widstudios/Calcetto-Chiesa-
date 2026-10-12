@extends('layouts.app')

@section('title', $coach->exists ? 'Modifica allenatore' : 'Nuovo allenatore')

@section('content')
    <h1>{{ $coach->exists ? 'Modifica allenatore' : 'Nuovo allenatore' }}</h1>

    <form class="card" method="POST" action="{{ $coach->exists ? route('coaches.update', $coach) : route('coaches.store') }}">
        @csrf
        @if ($coach->exists) @method('PUT') @endif

        <label for="name">Nome e cognome *</label>
        <input id="name" type="text" name="name" value="{{ old('name', $coach->name) }}" required>
        @error('name') <div class="error">{{ $message }}</div> @enderror

        <label for="email">Email (serve per accedere) *</label>
        <input id="email" type="email" name="email" value="{{ old('email', $coach->email) }}" required>
        @error('email') <div class="error">{{ $message }}</div> @enderror

        <label for="password">{{ $coach->exists ? 'Nuova password (lascia vuoto per non cambiarla)' : 'Password (almeno 8 caratteri) *' }}</label>
        <input id="password" type="password" name="password" autocomplete="new-password" @required(! $coach->exists)>
        @error('password') <div class="error">{{ $message }}</div> @enderror

        <label>Squadre che allena</label>
        @php($selected = old('teams', $coach->exists ? $coach->teams->pluck('id')->all() : []))
        @forelse ($teams as $team)
            <label style="margin: 4px 0">
                <input type="checkbox" name="teams[]" value="{{ $team->id }}" @checked(in_array($team->id, $selected))>
                {{ $team->name }} <span class="muted">({{ $team->tournament->name }})</span>
            </label>
        @empty
            <p class="muted">Nessuna squadra ancora: potrai assegnarla dopo.</p>
        @endforelse

        <button class="btn" type="submit">Salva</button>
        <a class="btn secondary" href="{{ route('coaches.index') }}">Annulla</a>
    </form>
@endsection
