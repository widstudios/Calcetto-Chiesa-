@extends('layouts.app')

@section('title', 'Cambia password')

@section('content')
    @include('partials.form-head', ['back' => route('dashboard'), 'eyebrow' => auth()->user()->email, 'title' => 'Cambia password'])

    <form class="card form-card" method="POST" action="{{ route('password.update') }}">
        @csrf
        @method('PUT')

        <label for="current_password" style="margin-top: 0">Password attuale *</label>
        <input id="current_password" type="password" name="current_password" autocomplete="current-password" required @error('current_password') aria-invalid="true" @enderror>
        @include('partials.error', ['field' => 'current_password'])

        <label for="password">Nuova password (almeno 8 caratteri) *</label>
        <input id="password" type="password" name="password" autocomplete="new-password" required @error('password') aria-invalid="true" @enderror>
        @include('partials.error', ['field' => 'password'])

        <label for="password_confirmation">Ripeti la nuova password *</label>
        <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required>

        <div class="form-actions">
            <button class="btn" type="submit">Salva</button>
            <a class="btn secondary" href="{{ route('dashboard') }}">Annulla</a>
        </div>
    </form>
@endsection
