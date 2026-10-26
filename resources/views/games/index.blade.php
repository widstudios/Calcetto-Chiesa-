@extends('layouts.app')

@section('title', 'Calendario')

@section('content')
    <div class="page-head">
        <div>
            <span class="eyebrow">{{ $tournament?->name ?? 'Calendario' }}</span>
            <h1>Calendario partite</h1>
        </div>
        @if ($tournament)
            <div class="actions">
                <a class="btn secondary" href="{{ route('games.pdf', $tournament) }}"><x-icon name="download" /> Scarica PDF</a>
                <a class="btn" href="{{ route('games.create', ['tournament' => $tournament->id]) }}"><x-icon name="plus" /> Nuova partita</a>
            </div>
        @endif
    </div>

    @if (! $tournament)
        <div class="card empty"><x-icon name="calendar" /><p>Prima crea un torneo e le sue squadre.</p></div>
    @else
        <form class="card flush filter" method="GET" action="{{ route('games.index') }}">
            <label for="tournament">Torneo</label>
            <select id="tournament" name="tournament" onchange="this.form.submit()">
                @foreach ($tournaments as $t)
                    <option value="{{ $t->id }}" @selected($t->is($tournament))>{{ $t->name }}</option>
                @endforeach
            </select>
        </form>

        @error('knockout')
            <div class="flash danger" role="alert">{{ $message }}</div>
        @enderror

        <div class="card generate-card">
            <form method="POST" action="{{ route('games.generate', $tournament) }}" class="generate-form">
                @csrf
                <div>
                    <strong>Calendario automatico</strong>
                    <p class="hint">{{ $tournament->hasGroups() ? 'Crea tutte le partite di ogni girone: ognuno incontra le altre squadre del suo girone.' : 'Crea tutte le partite: ogni squadra incontra tutte le altre.' }} Le partite già inserite restano come sono.</p>
                    <label class="inline-check"><input type="checkbox" name="return_leg" value="1"> Anche il ritorno</label>
                </div>
                <button class="btn secondary" type="submit"><x-icon name="calendar" /> Crea partite</button>
            </form>
            @if ($tournament->hasGroups())
                <form method="POST" action="{{ route('games.knockout', $tournament) }}" class="generate-form">
                    @csrf
                    <div>
                        <strong>Fase finale</strong>
                        <p class="hint">
                            @if ($tournament->qualifiers_per_group)
                                Passano le prime {{ $tournament->qualifiers_per_group }} di ogni girone. Si crea quando tutte le partite dei gironi sono giocate; i turni dopo si creano da soli inserendo i risultati.
                            @else
                                Per usarla indica nel torneo quante squadre per girone passano il turno.
                            @endif
                        </p>
                    </div>
                    <button class="btn secondary" type="submit" @disabled(! $tournament->qualifiers_per_group || $games->contains(fn ($g) => $g->isKnockout()))><x-icon name="trophy" /> Crea fase finale</button>
                </form>
            @endif
        </div>

        <div class="card flush">
            @if ($games->isEmpty())
                <div class="empty"><x-icon name="calendar" /><p>Nessuna partita in calendario.</p></div>
            @else
                @foreach ($games->groupBy(fn ($g) => $g->stage?->label() ?? '') as $stageLabel => $stageGames)
                    @if ($stageLabel !== '')
                        <h3 class="group-title">{{ $stageLabel }}</h3>
                    @endif
                    @foreach ($stageGames as $game)
                        @include('partials.game', ['game' => $game, 'editable' => true])
                    @endforeach
                @endforeach
            @endif
        </div>
    @endif
@endsection
