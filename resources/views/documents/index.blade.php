@extends('layouts.app')

@section('title', 'Documenti')

@section('content')
    <div class="page-head">
        <div>
            <span class="eyebrow">Area riservata</span>
            <h1>Documenti</h1>
        </div>
        <a class="btn" href="{{ route('documents.create') }}"><x-icon name="plus" /> Allega documento</a>
    </div>

    <div class="card flush">
        @if ($documents->isEmpty())
            <div class="empty"><x-icon name="file" /><p>Nessun documento. Allega il primo e scegli chi lo può vedere.</p></div>
        @else
            <ul class="list">
                @foreach ($documents as $document)
                    <li class="list-item">
                        <span class="shirt none doc-ext" aria-hidden="true">{{ $document->extension() }}</span>
                        <div class="list-main">
                            <a class="list-title" href="{{ route('documents.download', $document) }}" target="_blank" rel="noopener">{{ $document->title }}</a>
                            @if ($document->description)
                                <p class="hint" style="margin: 2px 0 4px">{{ $document->description }}</p>
                            @endif
                            <div class="list-meta">
                                <span><x-icon name="coach" /> {{ $document->author?->name ?? 'Utente eliminato' }}</span>
                                <span><x-icon name="clock" /> {{ $document->created_at->format('d/m/Y') }}</span>
                                <span>{{ $document->humanSize() }}</span>
                                @if ($document->for_everyone)
                                    <span class="badge">Tutti</span>
                                @else
                                    <span class="badge gold" title="{{ $document->viewers->pluck('name')->join(', ') }}"><x-icon name="lock" /> {{ $document->viewers->count() === 1 ? $document->viewers->first()->name : $document->viewers->count().' persone' }}</span>
                                @endif
                            </div>
                        </div>
                        <div class="actions">
                            <a class="btn small" href="{{ route('documents.download', [$document, 'scarica' => 1]) }}"><x-icon name="download" /> <span class="btn-text">Scarica</span></a>
                            @can('update', $document)
                                <a class="btn small secondary" href="{{ route('documents.edit', $document) }}"><x-icon name="pencil" /> <span class="btn-text">Modifica</span></a>
                                @include('partials.delete', ['action' => route('documents.destroy', $document), 'confirm' => 'Eliminare questo documento?'])
                            @endcan
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
