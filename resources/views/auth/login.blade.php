@extends('layouts.app')

@section('title', 'Accedi')

@section('content')
    <div class="auth">
        <div class="auth-card">
            <div class="auth-brand">
                <x-brand-mark />
                <div>
                    <strong>{{ \App\Models\Setting::siteName() }}</strong>
                    <span class="muted small">Gestionale del torneo</span>
                </div>
            </div>
            <h1>Accedi</h1>
            <p class="muted">Entra con l'email e la password che ti ha dato l'organizzazione.</p>

            <form method="POST" action="{{ url('/login') }}">
                @csrf
                <label for="email">Email</label>
                <input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required autofocus @error('email') aria-invalid="true" aria-describedby="email-error" @enderror>
                @error('email') <div class="error" id="email-error"><x-icon name="alert" /> {{ $message }}</div> @enderror

                <label for="password">Password</label>
                <input id="password" type="password" name="password" autocomplete="current-password" required>

                <label class="inline-check"><input type="checkbox" name="remember"> Ricordami su questo dispositivo</label>

                <button class="btn" type="submit">Entra</button>
            </form>
        </div>
    </div>
@endsection
