@extends('layouts.app')

@section('title', $player->exists ? 'Modifica calciatore' : 'Nuovo calciatore')

@section('content')
    <h1>{{ $player->exists ? 'Modifica calciatore' : 'Nuovo calciatore' }}</h1>
    <p class="muted">{{ $team->name }}</p>

    <form class="card" method="POST" action="{{ $player->exists ? route('teams.players.update', [$team, $player]) : route('teams.players.store', $team) }}">
        @csrf
        @if ($player->exists) @method('PUT') @endif

        <div class="grid">
            <div>
                <label for="first_name">Nome *</label>
                <input id="first_name" type="text" name="first_name" value="{{ old('first_name', $player->first_name) }}" required autofocus>
                @error('first_name') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="last_name">Cognome *</label>
                <input id="last_name" type="text" name="last_name" value="{{ old('last_name', $player->last_name) }}" required>
                @error('last_name') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="birth_year">Anno di nascita *</label>
                <input id="birth_year" type="number" name="birth_year" min="1940" max="{{ now()->year }}" value="{{ old('birth_year', $player->birth_year) }}" required>
                @error('birth_year') <div class="error">{{ $message }}</div> @enderror
            </div>
            <div>
                <label for="shirt_number">Numero di maglia</label>
                <input id="shirt_number" type="number" name="shirt_number" min="1" max="99" value="{{ old('shirt_number', $player->shirt_number) }}">
                @error('shirt_number') <div class="error">{{ $message }}</div> @enderror
            </div>
        </div>

        <label>Posizione in rosa *</label>
        @foreach (App\Enums\SquadRole::cases() as $role)
            <label style="margin: 4px 0">
                <input type="radio" name="squad_role" value="{{ $role->value }}" @checked(old('squad_role', $player->squad_role?->value) === $role->value)>
                {{ $role->label() }}
            </label>
        @endforeach
        @error('squad_role') <div class="error">{{ $message }}</div> @enderror

        <button class="btn" type="submit">Salva</button>
        @unless ($player->exists)
            <button class="btn secondary" type="submit" name="another" value="1">Salva e aggiungi un altro</button>
        @endunless
        <a class="btn secondary" href="{{ route('teams.show', $team) }}">Annulla</a>
    </form>
@endsection
