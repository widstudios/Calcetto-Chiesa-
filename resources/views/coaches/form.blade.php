@extends('layouts.app')

@section('title', $coach->exists ? 'Modifica allenatore' : 'Nuovo allenatore')

@section('content')
    @include('partials.form-head', ['back' => route('coaches.index'), 'title' => $coach->exists ? 'Modifica allenatore' : 'Nuovo allenatore'])

    <form class="card form-card" method="POST" action="{{ $coach->exists ? route('coaches.update', $coach) : route('coaches.store') }}">
        @csrf
        @if ($coach->exists) @method('PUT') @endif

        <label for="name" style="margin-top: 0">Nome e cognome *</label>
        <input id="name" type="text" name="name" value="{{ old('name', $coach->name) }}" required @error('name') aria-invalid="true" @enderror>
        @include('partials.error', ['field' => 'name'])

        <label for="email">Email *</label>
        <input id="email" type="email" name="email" value="{{ old('email', $coach->email) }}" autocomplete="off" required @error('email') aria-invalid="true" @enderror>
        @include('partials.error', ['field' => 'email'])

        <label for="password">{{ $coach->exists ? 'Nuova password (lascia vuoto per non cambiarla)' : 'Password (almeno 8 caratteri; vuota se non deve accedere)' }}</label>
        <input id="password" type="password" name="password" autocomplete="new-password" @error('password') aria-invalid="true" @enderror>
        @include('partials.error', ['field' => 'password'])

        <label for="csi_registered_on">Tesserato CSI il (vuoto se è ancora da tesserare)</label>
        <input id="csi_registered_on" type="date" name="csi_registered_on" value="{{ old('csi_registered_on', $coach->csi_registered_on?->format('Y-m-d')) }}" @error('csi_registered_on') aria-invalid="true" @enderror>
        @include('partials.error', ['field' => 'csi_registered_on'])

        <span class="legend">Squadre che allena</span>
        @php($selected = old('teams', $coach->exists ? $coach->teams->pluck('id')->all() : []))
        @forelse ($teams as $team)
            <label class="check">
                <input type="checkbox" name="teams[]" value="{{ $team->id }}" @checked(in_array($team->id, $selected))>
                <span>{{ $team->name }} <span class="muted">({{ $team->tournament->name }})</span></span>
            </label>
        @empty
            <p class="muted">Nessuna squadra ancora: potrai assegnarla dopo.</p>
        @endforelse

        <div class="form-actions">
            <button class="btn" type="submit">Salva</button>
            <a class="btn secondary" href="{{ route('coaches.index') }}">Annulla</a>
        </div>
    </form>
@endsection
