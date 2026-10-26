@extends('layouts.app')

@section('title', $referee->exists ? 'Modifica arbitro' : 'Nuovo arbitro')

@section('content')
    @include('partials.form-head', ['back' => route('referees.index'), 'title' => $referee->exists ? 'Modifica arbitro' : 'Nuovo arbitro'])

    <form class="card form-card" method="POST" action="{{ $referee->exists ? route('referees.update', $referee) : route('referees.store') }}">
        @csrf
        @if ($referee->exists) @method('PUT') @endif

        <div class="grid">
            <div>
                <label for="first_name" style="margin-top: 0">Nome *</label>
                <input id="first_name" type="text" name="first_name" value="{{ old('first_name', $referee->first_name) }}" required @error('first_name') aria-invalid="true" @enderror>
                @include('partials.error', ['field' => 'first_name'])
            </div>
            <div>
                <label for="last_name" style="margin-top: 0">Cognome *</label>
                <input id="last_name" type="text" name="last_name" value="{{ old('last_name', $referee->last_name) }}" required @error('last_name') aria-invalid="true" @enderror>
                @include('partials.error', ['field' => 'last_name'])
            </div>
        </div>

        <label for="phone">Telefono</label>
        <input id="phone" type="tel" name="phone" inputmode="tel" autocomplete="off" value="{{ old('phone', $referee->phone) }}" @error('phone') aria-invalid="true" @enderror>
        @include('partials.error', ['field' => 'phone'])
        <p class="hint">Lo vedono solo gli amministratori: non compare sul sito pubblico.</p>

        <span class="legend">Partite che arbitra</span>
        @php($selected = array_map('intval', old('games', $referee->exists ? $referee->games->pluck('id')->all() : [])))
        @forelse ($games as $game)
            <label class="check">
                <input type="checkbox" name="games[]" value="{{ $game->id }}" @checked(in_array($game->id, $selected))>
                <span>
                    {{ $game->homeTeam->name }} – {{ $game->awayTeam->name }}
                    <span class="muted">({{ $game->kickoff_at?->format('d/m/Y H:i') ?? 'data da definire' }} · {{ $game->tournament->name }})</span>
                </span>
            </label>
        @empty
            <p class="muted">Nessuna partita da assegnare: tutte quelle da giocare hanno già un arbitro.</p>
        @endforelse
        @include('partials.error', ['field' => 'games'])
        <p class="hint">Qui trovi le partite ancora senza arbitro. Puoi scegliere l'arbitro anche da Modifica partita.</p>

        <div class="form-actions">
            <button class="btn" type="submit">Salva</button>
            <a class="btn secondary" href="{{ route('referees.index') }}">Annulla</a>
        </div>
    </form>
@endsection
