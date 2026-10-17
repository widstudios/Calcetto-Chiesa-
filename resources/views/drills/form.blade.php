@extends('layouts.app')

@section('title', $drill->exists ? 'Modifica esercizio' : 'Nuovo esercizio')

@section('content')
    @include('partials.form-head', [
        'back' => $drill->exists ? route('drills.index', ['esercizio' => $drill->id]) : route('drills.index'),
        'eyebrow' => 'Libreria allenamenti',
        'title' => $drill->exists ? 'Modifica esercizio' : 'Nuovo esercizio',
    ])

    <form class="card form-card" method="POST" action="{{ $drill->exists ? route('drills.update', $drill) : route('drills.store') }}">
        @csrf
        @if ($drill->exists) @method('PUT') @endif

        <label for="name" style="margin-top: 0">Nome dell'esercizio *</label>
        <input id="name" type="text" name="name" value="{{ old('name', $drill->name) }}" required @error('name') aria-invalid="true" @enderror>
        @include('partials.error', ['field' => 'name'])

        <label for="category">Tipo di allenamento *</label>
        <select id="category" name="category" required>
            @foreach (App\Enums\DrillCategory::cases() as $case)
                <option value="{{ $case->value }}" @selected(old('category', $drill->category?->value) === $case->value)>{{ $case->label() }}</option>
            @endforeach
        </select>
        @include('partials.error', ['field' => 'category'])

        <div class="grid">
            <div>
                <label for="age_min">Età da *</label>
                <input id="age_min" type="number" name="age_min" min="4" max="18" value="{{ old('age_min', $drill->age_min) }}" required @error('age_min') aria-invalid="true" @enderror>
                @include('partials.error', ['field' => 'age_min'])
            </div>
            <div>
                <label for="age_max">Età fino a *</label>
                <input id="age_max" type="number" name="age_max" min="4" max="18" value="{{ old('age_max', $drill->age_max) }}" required @error('age_max') aria-invalid="true" @enderror>
                @include('partials.error', ['field' => 'age_max'])
            </div>
            <div>
                <label for="minutes">Durata (minuti)</label>
                <input id="minutes" type="number" name="minutes" min="1" max="120" value="{{ old('minutes', $drill->minutes) }}" @error('minutes') aria-invalid="true" @enderror>
                @include('partials.error', ['field' => 'minutes'])
            </div>
        </div>

        <div class="grid">
            <div>
                <label for="players">Giocatori</label>
                <input id="players" type="text" name="players" value="{{ old('players', $drill->players) }}" placeholder="es. 6-12, a coppie">
                @include('partials.error', ['field' => 'players'])
            </div>
            <div>
                <label for="equipment">Materiale</label>
                <input id="equipment" type="text" name="equipment" value="{{ old('equipment', $drill->equipment) }}" placeholder="es. 6 coni, 1 palla a testa">
                @include('partials.error', ['field' => 'equipment'])
            </div>
        </div>

        <label for="description">A cosa serve *</label>
        <textarea id="description" name="description" required @error('description') aria-invalid="true" @enderror>{{ old('description', $drill->description) }}</textarea>
        @include('partials.error', ['field' => 'description'])

        <label for="setup">Come si prepara il campo</label>
        <textarea id="setup" name="setup">{{ old('setup', $drill->setup) }}</textarea>
        @include('partials.error', ['field' => 'setup'])

        <label for="steps">Cosa devono fare (un passaggio per riga)</label>
        <textarea id="steps" name="steps" rows="6">{{ old('steps', $drill->steps) }}</textarea>
        @include('partials.error', ['field' => 'steps'])

        <label for="tips">Consigli per il mister (uno per riga)</label>
        <textarea id="tips" name="tips">{{ old('tips', $drill->tips) }}</textarea>
        @include('partials.error', ['field' => 'tips'])

        <label for="diagram">Schema disegnato</label>
        <select id="diagram" name="diagram">
            <option value="">Nessuno schema</option>
            @foreach (App\Models\Drill::DIAGRAMS as $key => $label)
                <option value="{{ $key }}" @selected(old('diagram', $drill->diagram) === $key)>{{ $label }}</option>
            @endforeach
        </select>
        @include('partials.error', ['field' => 'diagram'])

        <label for="video_url">Link del video YouTube</label>
        <input id="video_url" type="url" name="video_url" value="{{ old('video_url', $drill->video_url) }}" placeholder="https://www.youtube.com/watch?v=..." @error('video_url') aria-invalid="true" @enderror>
        <p class="hint">Copia il link dal tasto "Condividi" di YouTube: il video comparirà sotto l'esercizio.</p>
        @include('partials.error', ['field' => 'video_url'])

        <div class="grid">
            <div>
                <label for="video_title">Titolo del video</label>
                <input id="video_title" type="text" name="video_title" value="{{ old('video_title', $drill->video_title) }}">
                @include('partials.error', ['field' => 'video_title'])
            </div>
            <div>
                <label for="video_channel">Canale YouTube</label>
                <input id="video_channel" type="text" name="video_channel" value="{{ old('video_channel', $drill->video_channel) }}">
                @include('partials.error', ['field' => 'video_channel'])
            </div>
        </div>

        <div class="form-actions">
            <button class="btn" type="submit">Salva</button>
            <a class="btn secondary" href="{{ $drill->exists ? route('drills.index', ['esercizio' => $drill->id]) : route('drills.index') }}">Annulla</a>
        </div>
    </form>
@endsection
