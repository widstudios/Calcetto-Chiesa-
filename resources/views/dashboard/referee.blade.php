@extends('layouts.app')

@section('title', 'Le mie partite')

@section('content')
    <div class="page-head">
        <div>
            <span class="eyebrow">Ciao {{ auth()->user()->name }}</span>
            <h1>Le mie partite</h1>
        </div>
    </div>
    <p class="muted">Le partite che ti ha assegnato l'amministratore del torneo. Qui puoi solo consultarle: per cambiare data, campo o risultato contatta l'organizzazione.</p>

    <section class="card flush">
        <div class="card-head">
            <h2>Da arbitrare</h2>
        </div>
        @forelse ($upcoming as $game)
            <p class="muted small" style="margin: 12px 16px 0">{{ $game->tournament->name }}</p>
            @include('partials.game', ['game' => $game])
        @empty
            <div class="empty"><x-icon name="whistle" /><p>Nessuna partita in programma per te.</p></div>
        @endforelse
    </section>

    @if ($played->isNotEmpty())
        <section class="card flush">
            <div class="card-head">
                <h2>Già arbitrate</h2>
            </div>
            @foreach ($played as $game)
                <p class="muted small" style="margin: 12px 16px 0">{{ $game->tournament->name }}</p>
                @include('partials.game', ['game' => $game, 'sheet' => true])
            @endforeach
        </section>
    @endif
@endsection
