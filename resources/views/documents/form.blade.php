@extends('layouts.app')

@section('title', $document->exists ? 'Modifica documento' : 'Allega documento')

@section('content')
    @include('partials.form-head', ['back' => route('documents.index'), 'eyebrow' => 'Documenti', 'title' => $document->exists ? 'Modifica documento' : 'Allega documento'])

    @php($visibility = old('visibility', $document->for_everyone ? 'tutti' : 'scelta'))
    @php($chosen = array_map('intval', old('viewers', $chosen)))

    <form class="card form-card" method="POST" enctype="multipart/form-data" action="{{ $document->exists ? route('documents.update', $document) : route('documents.store') }}">
        @csrf
        @if ($document->exists) @method('PUT') @endif

        <label for="title" style="margin-top: 0">Titolo *</label>
        <input id="title" type="text" name="title" value="{{ old('title', $document->title) }}" placeholder="Es. Modulo iscrizione torneo" required maxlength="255" @error('title') aria-invalid="true" @enderror>
        @include('partials.error', ['field' => 'title'])

        <label for="file">File {{ $document->exists ? '' : '*' }}</label>
        <input id="file" type="file" name="file" @unless ($document->exists) required @endunless
               accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.odt,.ods,.odp,.txt,.csv,.jpg,.jpeg,.png,.webp,.gif,.zip" @error('file') aria-invalid="true" @enderror>
        <p class="hint">
            @if ($document->exists)
                File attuale: <strong>{{ $document->original_name }}</strong> ({{ $document->humanSize() }}). Scegline un altro solo se vuoi sostituirlo.
            @else
                PDF, Word, Excel, PowerPoint, immagini, testo o ZIP, fino a 20 MB.
            @endif
        </p>
        @include('partials.error', ['field' => 'file'])

        <label for="description">Descrizione</label>
        <textarea id="description" name="description" maxlength="2000" placeholder="Facoltativa">{{ old('description', $document->description) }}</textarea>
        @include('partials.error', ['field' => 'description'])

        <fieldset class="doc-visibility" style="border: 0; padding: 0; margin: 0">
            <legend class="legend">Chi può vederlo *</legend>
            <div class="choices">
                <label class="choice"><input type="radio" name="visibility" value="tutti" @checked($visibility === 'tutti')> <x-icon name="users" /> Tutti</label>
                <label class="choice"><input type="radio" name="visibility" value="scelta" @checked($visibility === 'scelta')> <x-icon name="lock" /> Scegli le persone</label>
            </div>
            @include('partials.error', ['field' => 'visibility'])

            <div class="doc-people">
                <p class="hint" style="margin: 12px 0 8px">Spunta chi può vederlo. Tu lo vedi sempre, e anche l'amministratore.</p>
                @if ($people->isEmpty())
                    <p class="hint">Non ci sono altri utenti nell'app.</p>
                @else
                    <div class="choices">
                        @foreach ($people as $person)
                            <label class="choice">
                                <input type="checkbox" name="viewers[]" value="{{ $person->id }}" @checked(in_array($person->id, $chosen, true))>
                                <span>{{ $person->name }} <small class="hint" style="display: block; margin: 0">{{ $person->role?->label() }}</small></span>
                            </label>
                        @endforeach
                    </div>
                @endif
                @include('partials.error', ['field' => 'viewers'])
            </div>
        </fieldset>

        <div class="form-actions">
            <button class="btn" type="submit">{{ $document->exists ? 'Salva' : 'Allega' }}</button>
            <a class="btn secondary" href="{{ route('documents.index') }}">Annulla</a>
        </div>
    </form>

    @if ($document->exists)
        <div style="margin-top: 16px">
            @include('partials.delete', ['action' => route('documents.destroy', $document), 'confirm' => 'Eliminare questo documento?', 'label' => 'Elimina documento'])
        </div>
    @endif
@endsection
