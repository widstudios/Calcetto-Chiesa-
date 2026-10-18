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
        @if ($syncedAt) Ultimo controllo: {{ $syncedAt->format('d/m/Y \a\l\l\e H:i') }}. @endif</p>

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
                            </div>
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
