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
                    <label class="inline-check"><input type="checkbox" name="return_leg" value="1" @checked(old('return_leg'))> Anche il ritorno</label>
                    <details class="schedule-fields" @if ($errors->hasAny(['first_day', 'from', 'to', 'minutes'])) open @endif>
                        <summary>Dai anche data e ora (un solo campo)</summary>
                        <div class="grid">
                            <div>
                                <label for="first_day">Primo giorno</label>
                                <input id="first_day" type="date" name="first_day" value="{{ old('first_day', $tournament->starts_on?->format('Y-m-d')) }}">
                                @include('partials.error', ['field' => 'first_day'])
                            </div>
                            <div>
                                <label for="from">Dalle</label>
                                <input id="from" type="time" name="from" value="{{ old('from', '15:00') }}">
                                @include('partials.error', ['field' => 'from'])
                            </div>
                            <div>
                                <label for="to">Alle</label>
                                <input id="to" type="time" name="to" value="{{ old('to', '19:00') }}">
                                @include('partials.error', ['field' => 'to'])
                            </div>
                            <div>
                                <label for="minutes">Minuti a partita</label>
                                <input id="minutes" type="number" inputmode="numeric" min="5" max="180" name="minutes" value="{{ old('minutes', 20) }}">
                                @include('partials.error', ['field' => 'minutes'])
                            </div>
                        </div>
                        <label for="venue">Campo</label>
                        <input id="venue" type="text" name="venue" value="{{ old('venue') }}">
                        <p class="hint">Le partite vanno una dopo l'altra, alternando i gironi, e saltano gli orari già occupati da partite di altri tornei. Se il pomeriggio non basta si continua lo stesso giorno della settimana dopo. Lascia vuoto il primo giorno per creare le partite senza orario.</p>
                    </details>
                </div>
                <button class="btn secondary" type="submit"><x-icon name="calendar" /> Crea partite</button>
            </form>
            @if ($tournament->hasGroups() || $tournament->qualifiers_per_group)
                <form method="POST" action="{{ route('games.knockout', $tournament) }}" class="generate-form">
                    @csrf
                    <div>
                        <strong>Fase finale</strong>
                        <p class="hint">
                            @if ($tournament->qualifiers_per_group)
                                Passano le prime {{ $tournament->qualifiers_per_group }}{{ $tournament->hasGroups() ? ' di ogni girone' : ' in classifica' }}.
                                @if ($tournament->two_leg_stages)
                                    Con andata e ritorno: {{ collect($tournament->two_leg_stages)->map(fn ($s) => \App\Enums\GameStage::from($s)->label())->join(', ') }}.
                                @endif
                                Si crea quando tutte le partite {{ $tournament->hasGroups() ? 'dei gironi' : 'del girone' }} sono giocate; i turni dopo si creano da soli inserendo i risultati.
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
