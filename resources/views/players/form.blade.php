@extends('layouts.app')

@section('title', $player->exists ? 'Modifica calciatore' : 'Nuovo calciatore')

@section('content')
    @include('partials.form-head', ['back' => route('teams.show', $team), 'eyebrow' => $team->name, 'title' => $player->exists ? 'Modifica calciatore' : 'Nuovo calciatore'])

    <form class="card form-card" method="POST" action="{{ $player->exists ? route('teams.players.update', [$team, $player]) : route('teams.players.store', $team) }}">
        @csrf
        @if ($player->exists) @method('PUT') @endif

        <div class="grid">
            <div>
                <label for="first_name">Nome *</label>
                <input id="first_name" type="text" name="first_name" value="{{ old('first_name', $player->first_name) }}" autocomplete="off" required autofocus @error('first_name') aria-invalid="true" @enderror>
                @include('partials.error', ['field' => 'first_name'])
            </div>
            <div>
                <label for="last_name">Cognome *</label>
                <input id="last_name" type="text" name="last_name" value="{{ old('last_name', $player->last_name) }}" autocomplete="off" required @error('last_name') aria-invalid="true" @enderror>
                @include('partials.error', ['field' => 'last_name'])
            </div>
            <div>
                <label for="birth_year">Anno di nascita *</label>
                <input id="birth_year" type="number" inputmode="numeric" name="birth_year" min="1940" max="{{ now()->year }}" value="{{ old('birth_year', $player->birth_year) }}" required @error('birth_year') aria-invalid="true" @enderror>
                @include('partials.error', ['field' => 'birth_year'])
            </div>
            <div>
                <label for="shirt_number">Numero di maglia</label>
                <input id="shirt_number" type="number" inputmode="numeric" name="shirt_number" min="1" max="99" value="{{ old('shirt_number', $player->shirt_number) }}" @error('shirt_number') aria-invalid="true" @enderror>
                @include('partials.error', ['field' => 'shirt_number'])
            </div>
            <div>
                <label for="phone">Cellulare</label>
                <input id="phone" type="tel" inputmode="tel" name="phone" maxlength="30" value="{{ old('phone', $player->phone) }}" autocomplete="off" @error('phone') aria-invalid="true" @enderror>
                @include('partials.error', ['field' => 'phone'])
            </div>
            <div>
                <label for="phone_2">Secondo cellulare</label>
                <input id="phone_2" type="tel" inputmode="tel" name="phone_2" maxlength="30" value="{{ old('phone_2', $player->phone_2) }}" autocomplete="off" @error('phone_2') aria-invalid="true" @enderror>
                @include('partials.error', ['field' => 'phone_2'])
            </div>
        </div>

        <span class="legend">Posizione in rosa *</span>
        <div class="choices">
            @foreach (App\Enums\SquadRole::cases() as $role)
                <label class="choice">
                    <input type="radio" name="squad_role" value="{{ $role->value }}" @checked(old('squad_role', $player->squad_role?->value) === $role->value)>
                    {{ $role->label() }}
                </label>
            @endforeach
        </div>
        @include('partials.error', ['field' => 'squad_role'])

        <div class="form-actions">
            <button class="btn" type="submit">Salva</button>
            @unless ($player->exists)
                <button class="btn secondary" type="submit" name="another" value="1"><x-icon name="plus" /> Salva e aggiungi un altro</button>
            @endunless
            <a class="btn secondary" href="{{ route('teams.show', $team) }}">Annulla</a>
        </div>
    </form>
@endsection
