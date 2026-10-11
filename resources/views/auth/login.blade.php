@extends('layouts.app')

@section('title', 'Accedi')

@section('content')
    <div class="card" style="max-width: 400px; margin: 40px auto;">
        <h1>Accedi</h1>
        <form method="POST" action="{{ url('/login') }}">
            @csrf
            <label for="email">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus>
            @error('email') <div class="error">{{ $message }}</div> @enderror

            <label for="password">Password</label>
            <input id="password" type="password" name="password" required>

            <label><input type="checkbox" name="remember"> Ricordami</label>

            <button class="btn" type="submit">Entra</button>
        </form>
    </div>
@endsection
