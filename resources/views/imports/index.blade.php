@extends('layouts.app')

@section('title', 'Importa da Excel')

@section('content')
    <div class="page-head">
        <div>
            <span class="eyebrow">Caricamento dati</span>
            <h1>Importa da Excel</h1>
        </div>
    </div>
    <p class="muted">Scarica il modello, compilalo in Excel (una riga per dato, senza cambiare la prima riga) e caricalo qui: prima di salvare vedi un'anteprima.
        Puoi caricare lo stesso file più volte: i dati già presenti vengono aggiornati, non duplicati.
        Conviene seguire quest'ordine: tornei, squadre, allenatori, calciatori, partite, allenamenti.</p>

    @error('file')
        <div class="card"><div class="error" style="margin: 0"><x-icon name="alert" /> {{ $message }}</div></div>
    @enderror

    @foreach ($importers as $type => $importer)
        <section class="card" id="{{ $type }}">
            <div class="card-head">
                <h2 style="margin: 0">{{ $importer->label() }}</h2>
                <a class="btn small secondary" href="{{ route('imports.template', $type) }}"><x-icon name="download" /> Scarica il modello</a>
            </div>
            <p>{{ $importer->description() }}</p>
            <ul class="import-columns">
                @foreach ($importer->columns() as $column => $help)
                    <li><code>{{ rtrim($column, '*') }}</code>@if (str_ends_with($column, '*')) <strong>obbligatoria</strong>@endif · {{ $help }}</li>
                @endforeach
            </ul>
            <form class="import-form" method="POST" action="{{ route('imports.preview', $type) }}" enctype="multipart/form-data">
                @csrf
                <label class="sr-only" for="file-{{ $type }}">File con {{ Str::lower($importer->label()) }}</label>
                <input id="file-{{ $type }}" type="file" name="file" accept=".xlsx,.csv" required>
                <button class="btn" type="submit"><x-icon name="upload" /> Carica e controlla</button>
            </form>
        </section>
    @endforeach
@endsection
