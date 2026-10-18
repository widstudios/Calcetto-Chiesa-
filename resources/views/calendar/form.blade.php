@extends('layouts.app')

@section('title', $event->exists ? 'Modifica evento' : 'Nuovo evento')

@section('content')
    @include('partials.form-head', ['back' => route('calendar.index', ['settimana' => $event->date->startOfWeek()->toDateString()]), 'eyebrow' => 'Calendario', 'title' => $event->exists ? 'Modifica evento' : 'Nuovo evento'])

    <form class="card form-card" method="POST" action="{{ $event->exists ? route('calendar.events.update', $event) : route('calendar.events.store') }}">
        @csrf
        @if ($event->exists) @method('PUT') @endif

        <label for="title" style="margin-top: 0">Titolo *</label>
        <input id="title" type="text" name="title" value="{{ old('title', $event->title) }}" placeholder="Es. Riunione allenatori" required maxlength="255" @error('title') aria-invalid="true" @enderror>
        @include('partials.error', ['field' => 'title'])

        <div class="grid">
            <div>
                <label for="type">Tipo *</label>
                <select id="type" name="type" required>
                    @foreach (App\Enums\EventType::cases() as $type)
                        <option value="{{ $type->value }}" @selected(old('type', $event->type?->value) === $type->value)>{{ $type->label() }}</option>
                    @endforeach
                </select>
                @include('partials.error', ['field' => 'type'])
            </div>
            <div>
                <label for="team_id">Per</label>
                <select id="team_id" name="team_id" @error('team_id') aria-invalid="true" @enderror>
                    <option value="">Tutta la società</option>
                    @foreach ($teams as $team)
                        <option value="{{ $team->id }}" @selected(old('team_id', $event->team_id) == $team->id)>{{ $team->name }}</option>
                    @endforeach
                </select>
                @include('partials.error', ['field' => 'team_id'])
            </div>
        </div>

        <div class="grid">
            <div>
                <label for="date">Giorno *</label>
                <input id="date" type="date" name="date" value="{{ old('date', $event->date?->toDateString()) }}" required @error('date') aria-invalid="true" @enderror>
                @include('partials.error', ['field' => 'date'])
            </div>
            <div>
                <label for="starts_at">Dalle *</label>
                <input id="starts_at" type="time" name="starts_at" value="{{ old('starts_at', $event->starts_at ? substr($event->starts_at, 0, 5) : '') }}" required @error('starts_at') aria-invalid="true" @enderror>
                @include('partials.error', ['field' => 'starts_at'])
            </div>
            <div>
                <label for="ends_at">Alle</label>
                <input id="ends_at" type="time" name="ends_at" value="{{ old('ends_at', $event->ends_at ? substr($event->ends_at, 0, 5) : '') }}" @error('ends_at') aria-invalid="true" @enderror>
                @include('partials.error', ['field' => 'ends_at'])
            </div>
        </div>

        <label for="location">Luogo</label>
        <input id="location" type="text" name="location" value="{{ old('location', $event->location) }}" placeholder="Es. Sala riunioni dell'oratorio" maxlength="255">
        @include('partials.error', ['field' => 'location'])

        <label for="notes">Note</label>
        <textarea id="notes" name="notes" maxlength="2000">{{ old('notes', $event->notes) }}</textarea>
        @include('partials.error', ['field' => 'notes'])

        <div class="form-actions">
            <button class="btn" type="submit">Salva</button>
            <a class="btn secondary" href="{{ route('calendar.index', ['settimana' => $event->date->startOfWeek()->toDateString()]) }}">Annulla</a>
        </div>
    </form>

    @if ($event->exists)
        <div style="margin-top: 16px">
            @include('partials.delete', ['action' => route('calendar.events.destroy', $event), 'confirm' => 'Eliminare questo evento dal calendario?', 'label' => 'Elimina evento'])
        </div>
    @endif
@endsection
