@extends('layouts.app')

@section('title', 'Comunicati CSI')

@section('content')
    <div class="page-head">
        <div>
            <span class="eyebrow">Oratorio Cup · CSI Roma</span>
            <h1>Comunicati CSI</h1>
        </div>
        @if ($configured)
            <form method="POST" action="{{ route('csi.sync') }}">
                @csrf
                <button class="btn" type="submit"><x-icon name="download" /> Sincronizza ora</button>
            </form>
        @endif
    </div>
    <p class="muted">Il gestionale controlla da solo la <a href="{{ $folderUrl }}" target="_blank" rel="noopener">cartella dei documenti ufficiali dell'Oratorio Cup</a> e scarica qui ogni file nuovo, così non devi caricarli a mano.
        Dai comunicati legge calendario e risultati delle nostre partite e del nostro girone; i marcatori nei comunicati non ci sono, si inseriscono con il tabellino.
        @if ($syncedAt) Ultimo controllo: {{ $syncedAt->format('d/m/Y \a\l\l\e H:i') }}. @endif</p>

    <section class="card">
        <form method="POST" action="{{ route('csi.club') }}" class="import-form">
            @csrf
            @method('PUT')
            <div style="flex: 1">
                <label for="csi_club_name">Nome della nostra società nei comunicati</label>
                <input id="csi_club_name" type="text" name="csi_club_name" value="{{ old('csi_club_name', $clubName) }}" placeholder="Per esempio: S. Nicola" maxlength="80">
                @include('partials.error', ['field' => 'csi_club_name'])
                <p class="muted small" style="margin: .25rem 0 0">Scrivilo come lo scrive il CSI. Le partite vanno nel torneo con il nome della categoria (es. «Under 10»); le squadre avversarie si creano da sole.</p>
            </div>
            <button class="btn secondary" type="submit"><x-icon name="check" /> Salva</button>
        </form>
        @if ($clubName && $documents->isNotEmpty())
            <form method="POST" action="{{ route('csi.import') }}" style="margin-top: .75rem">
                @csrf
                <button class="btn small secondary" type="submit"><x-icon name="calendar" /> Rileggi i comunicati</button>
            </form>
        @endif
    </section>

    @if (! $configured)
        <div class="card"><div class="error" style="margin: 0"><x-icon name="alert" /> Manca la chiave Google: aggiungi <code>GOOGLE_API_KEY</code> nel file <code>.env</code> del server, poi torna qui.</div></div>
    @endif
    @error('sync')
        <div class="card"><div class="error" style="margin: 0"><x-icon name="alert" /> {{ $message }}</div></div>
    @else
        @if ($error)
            <div class="card"><div class="error" style="margin: 0"><x-icon name="alert" /> L'ultimo controllo automatico non è riuscito: {{ $error }}</div></div>
        @endif
    @enderror

    <div class="card flush">
        @if ($documents->isEmpty())
            <div class="empty"><x-icon name="book" /><p>Nessun documento scaricato.@if ($configured) Premi «Sincronizza ora» per il primo controllo.@endif</p></div>
        @else
            <ul class="list">
                @foreach ($documents as $document)
                    <li class="list-item">
                        <span class="avatar"><x-icon name="book" /></span>
                        <div class="list-main">
                            <div class="list-title">
                                {{ $document->name }}
                                @if ($document->downloaded_at?->gt(now()->subDay()))
                                    <span class="badge gold">Nuovo</span>
                                @endif
                            </div>
                            <div class="list-meta">
                                @if ($document->drive_modified_at)
                                    <span><x-icon name="calendar" /> Pubblicato il {{ $document->drive_modified_at->timezone(config('app.timezone'))->format('d/m/Y') }}</span>
                                @endif
                                @if ($document->humanSize())
                                    <span>{{ $document->humanSize() }}</span>
                                @endif
                                @if ($document->imported_at && $document->games_found)
                                    <span><x-icon name="ball" /> {{ $document->games_found }} {{ $document->games_found === 1 ? 'partita letta' : 'partite lette' }}, {{ $document->games_saved }} {{ $document->games_saved === 1 ? 'aggiornata' : 'aggiornate' }}</span>
                                @endif
                            </div>
                            @if ($document->import_note)
                                <div class="muted small">{{ $document->import_note }}</div>
                            @endif
                        </div>
                        <div class="actions">
                            <a class="btn small secondary" href="{{ route('csi.show', $document) }}" target="_blank" rel="noopener"><x-icon name="download" /> <span class="btn-text">Apri</span></a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
