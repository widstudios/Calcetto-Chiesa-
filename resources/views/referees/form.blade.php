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

        <span class="legend">Accesso all'app</span>
        <p class="hint" style="margin-top: 0">Con email e password l'arbitro entra nell'app e vede solo le partite che arbitra, senza poter modificare nulla. Lascia l'email vuota se non deve accedere (svuotala per togliergli l'accesso).</p>
        <label for="email">Email</label>
        <input id="email" type="email" name="email" value="{{ old('email', $referee->user?->email) }}" autocomplete="off" @error('email') aria-invalid="true" @enderror>
        @include('partials.error', ['field' => 'email'])

        <label for="password">{{ $referee->user ? 'Nuova password (lascia vuoto per non cambiarla)' : 'Password (almeno 8 caratteri)' }}</label>
        <input id="password" type="password" name="password" autocomplete="new-password" @error('password') aria-invalid="true" @enderror>
        @include('partials.error', ['field' => 'password'])

        <span class="legend">Partite che arbitra</span>
        {{-- Segnala che il modulo è stato inviato: dopo un errore resta anche la scelta "nessuna partita" --}}
        <input type="hidden" name="games_sent" value="1">
        @php($selected = array_map('intval', old('games_sent') ? old('games', []) : ($referee->exists ? $referee->games->pluck('id')->all() : [])))
        @forelse ($games as $game)
            <label class="check">
                <input type="checkbox" name="games[]" value="{{ $game->id }}" @checked(in_array($game->id, $selected))>
                <span>
                    {{ $game->homeTeam->name }} – {{ $game->awayTeam->name }}
                    <span class="muted">({{ $game->kickoff_at?->format('d/m/Y H:i') ?? 'data da definire' }} · {{ $game->tournament->name }})</span>
                </span>
            </label>
        @empty
            @if ($hasUpcoming)
                <p class="muted">Nessuna partita da assegnare: tutte quelle da giocare hanno già un arbitro.</p>
            @else
                <p class="muted">Non ci sono ancora partite da giocare. Salva l'arbitro, poi crea le partite in <a href="{{ route('games.index') }}">Calendario partite</a> e scegli l'arbitro dentro ogni partita.</p>
            @endif
        @endforelse
        @include('partials.error', ['field' => 'games'])
        <p class="hint">Qui trovi le partite ancora senza arbitro. Puoi scegliere l'arbitro anche da Modifica partita.</p>

        <div class="form-actions">
            <button class="btn" type="submit">Salva</button>
            <a class="btn secondary" href="{{ route('referees.index') }}">Annulla</a>
        </div>
    </form>
@endsection
