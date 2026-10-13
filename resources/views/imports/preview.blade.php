@extends('layouts.app')

@section('title', 'Anteprima import')

@section('content')
    @include('partials.form-head', ['back' => route('imports.index').'#'.$type, 'eyebrow' => 'Importa '.Str::lower($importer->label()), 'title' => $fileName])

    @if ($rowErrors !== [])
        <section class="card flush">
            <div class="card-head" style="padding: 20px 20px 0">
                <h2 style="margin: 0">{{ count($rowErrors) }} {{ count($rowErrors) === 1 ? 'riga da correggere' : 'righe da correggere' }}</h2>
            </div>
            <p class="muted" style="padding: 0 20px">Non è stato salvato niente. Correggi queste righe nel file e caricalo di nuovo.</p>
            <ul class="row-errors">
                @foreach ($rowErrors as $line => $message)
                    <li><x-icon name="alert" /> <strong>Riga {{ $line }}</strong> {{ $message }}</li>
                @endforeach
            </ul>
        </section>

        <form class="card import-form" method="POST" action="{{ route('imports.preview', $type) }}" enctype="multipart/form-data" style="border-top: 0">
            @csrf
            <label class="sr-only" for="file">File corretto</label>
            <input id="file" type="file" name="file" accept=".xlsx,.csv" required>
            <button class="btn" type="submit"><x-icon name="upload" /> Carica di nuovo</button>
        </form>
    @else
        @php($existing = collect($preview)->where('exists', true)->count())
        <div class="stats">
            <div class="stat">
                <span class="stat-icon"><x-icon name="plus" /></span>
                <div><div class="stat-value">{{ count($preview) - $existing }}</div><div class="stat-label">Da aggiungere</div></div>
            </div>
            <div class="stat">
                <span class="stat-icon gold"><x-icon name="pencil" /></span>
                <div><div class="stat-value">{{ $existing }}</div><div class="stat-label">Già presenti, da aggiornare</div></div>
            </div>
        </div>

        <form class="card" method="POST" action="{{ route('imports.store', $type) }}">
            @csrf
            <p style="margin: 0">Controlla le righe qui sotto. Niente è stato ancora salvato.</p>
            <div class="form-actions" style="margin-top: 16px; padding-top: 0; border: 0">
                <button class="btn" type="submit"><x-icon name="check" /> Conferma e salva {{ count($preview) }} righe</button>
                <a class="btn secondary" href="{{ route('imports.index') }}">Annulla</a>
            </div>
        </form>

        <div class="card flush table-wrap">
            <table class="stack">
                <thead><tr><th>Riga</th><th>{{ $importer->label() }}</th><th>Esito</th></tr></thead>
                <tbody>
                    @foreach ($preview as $line => $row)
                        <tr>
                            <td class="num" data-label="Riga">{{ $line }}</td>
                            <td class="title">{{ $row['summary'] }}</td>
                            <td data-label="Esito">
                                @if ($row['exists'])
                                    <span class="badge gold">Aggiorna</span>
                                @else
                                    <span class="badge">Nuovo</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
