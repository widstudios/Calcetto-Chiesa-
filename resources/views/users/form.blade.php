@extends('layouts.app')

@section('title', $user->exists ? 'Modifica utente' : 'Nuovo utente')

@section('content')
    @include('partials.form-head', ['back' => route('users.index'), 'eyebrow' => 'Utenti e ruoli', 'title' => $user->exists ? 'Modifica utente' : 'Nuovo utente'])

    <form class="card form-card" method="POST" action="{{ $user->exists ? route('users.update', $user) : route('users.store') }}">
        @csrf
        @if ($user->exists) @method('PUT') @endif

        <label for="name" style="margin-top: 0">Nome e cognome *</label>
        <input id="name" type="text" name="name" value="{{ old('name', $user->name) }}" required @error('name') aria-invalid="true" @enderror>
        @include('partials.error', ['field' => 'name'])

        <label for="email">Email *</label>
        <input id="email" type="email" name="email" value="{{ old('email', $user->email) }}" autocomplete="off" required @error('email') aria-invalid="true" @enderror>
        @include('partials.error', ['field' => 'email'])

        <label for="role">Ruolo *</label>
        @php($isSelf = $user->exists && $user->is(auth()->user()))
        <select id="role" name="role" required @error('role') aria-invalid="true" @enderror>
            @foreach (\App\Enums\Role::cases() as $role)
                <option value="{{ $role->value }}" @selected(old('role', $user->role?->value) === $role->value) @disabled($isSelf && $role !== \App\Enums\Role::Admin)>{{ $role->label() }}</option>
            @endforeach
        </select>
        @if ($isSelf)
            <p class="muted">Non puoi toglierti da solo il ruolo di amministratore.</p>
        @endif
        @include('partials.error', ['field' => 'role'])

        <label for="password">{{ $user->exists ? 'Nuova password (lascia vuoto per non cambiarla)' : 'Password * (almeno 8 caratteri)' }}</label>
        <input id="password" type="password" name="password" autocomplete="new-password" @required(! $user->exists) @error('password') aria-invalid="true" @enderror>
        @include('partials.error', ['field' => 'password'])

        <div class="form-actions">
            <button class="btn" type="submit">Salva</button>
            <a class="btn secondary" href="{{ route('users.index') }}">Annulla</a>
        </div>
    </form>
@endsection
