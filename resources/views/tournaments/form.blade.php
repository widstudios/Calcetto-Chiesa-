@extends('layouts.app')

@section('title', $tournament->exists ? 'Modifica torneo' : 'Nuovo torneo')

@section('content')
    @include('partials.form-head', ['back' => route('tournaments.index'), 'title' => $tournament->exists ? 'Modifica torneo' : 'Nuovo torneo'])

    <form class="card form-card" method="POST" action="{{ $tournament->exists ? route('tournaments.update', $tournament) : route('tournaments.store') }}">
        @csrf
        @if ($tournament->exists) @method('PUT') @endif

        <label for="name" style="margin-top: 0">Nome del torneo *</label>
        <input id="name" type="text" name="name" value="{{ old('name', $tournament->name) }}" required @error('name') aria-invalid="true" @enderror>
        @include('partials.error', ['field' => 'name'])

        <div class="grid">
            <div>
                <label for="season">Stagione</label>
                <input id="season" type="text" name="season" placeholder="2026/2027" value="{{ old('season', $tournament->season) }}">
                @include('partials.error', ['field' => 'season'])
            </div>
            <div>
                <label for="starts_on">Inizio</label>
                <input id="starts_on" type="date" name="starts_on" value="{{ old('starts_on', $tournament->starts_on?->format('Y-m-d')) }}">
                @include('partials.error', ['field' => 'starts_on'])
            </div>
            <div>
                <label for="ends_on">Fine</label>
                <input id="ends_on" type="date" name="ends_on" value="{{ old('ends_on', $tournament->ends_on?->format('Y-m-d')) }}">
                @include('partials.error', ['field' => 'ends_on'])
            </div>
        </div>

        <fieldset class="swatch-group" style="margin-top: 16px">
            <legend class="legend">Formula *</legend>
            @php($format = old('format', $tournament->format?->value ?? 'campionato'))
            <div class="choices">
                @foreach (App\Enums\TournamentFormat::cases() as $case)
                    <label class="choice"><input type="radio" name="format" value="{{ $case->value }}" @checked($format === $case->value) data-format> <span><strong>{{ $case->label() }}</strong><br><span class="muted small">{{ $case->description() }}</span></span></label>
                @endforeach
            </div>
            @include('partials.error', ['field' => 'format'])
        </fieldset>

        <div id="groups-count-field" @if ($format !== 'gironi') hidden @endif>
            <label for="groups_count">Quanti gironi *</label>
            <select id="groups_count" name="groups_count" style="max-width: 160px" @error('groups_count') aria-invalid="true" @enderror>
                @foreach (range(1, App\Models\Tournament::MAX_GROUPS) as $n)
                    <option value="{{ $n }}" @selected((int) old('groups_count', $tournament->groups_count ?? 2) === $n)>{{ $n }} ({{ implode(', ', array_slice(range('A', 'Z'), 0, $n)) }})</option>
                @endforeach
            </select>
            <p class="hint">Ogni girone ha la sua classifica. Marcatori, voti e migliori in campo restano di tutto il torneo.</p>
            @include('partials.error', ['field' => 'groups_count'])

            <label for="qualifiers_per_group">Squadre che passano alla fase finale, per girone</label>
            <select id="qualifiers_per_group" name="qualifiers_per_group" style="max-width: 240px" @error('qualifiers_per_group') aria-invalid="true" @enderror>
                <option value="">Nessuna fase finale</option>
                @foreach (range(1, 8) as $n)
                    <option value="{{ $n }}" @selected((int) old('qualifiers_per_group', $tournament->qualifiers_per_group) === $n)>{{ $n === 1 ? 'La prima' : 'Le prime '.$n }}</option>
                @endforeach
            </select>
            <p class="hint">Dopo i gironi si gioca a eliminazione diretta (ottavi, quarti, semifinali, finale). In tutto devono passare 2, 4, 8 o 16 squadre: per esempio 4 gironi con le prime 2 fanno i quarti di finale.</p>
            @include('partials.error', ['field' => 'qualifiers_per_group'])
        </div>

        @if ($tournament->exists && $tournament->hasGroups())
            <span class="legend">Squadre nei gironi</span>
            @if ($teams->isEmpty())
                <p class="muted">Nessuna squadra nel torneo. <a href="{{ route('teams.create', ['tournament' => $tournament->id]) }}">Aggiungi le squadre</a>, poi torna qui per dividerle.</p>
            @else
                <div class="group-picker">
                    @foreach ($teams as $team)
                        <label class="group-row" for="group-{{ $team->id }}">
                            <span class="team-line"><x-team-badge :team="$team" size="sm" /> {{ $team->name }}</span>
                            <select id="group-{{ $team->id }}" name="groups[{{ $team->id }}]">
                                <option value="">Nessun girone</option>
                                @foreach ($tournament->groupNames() as $g)
                                    <option value="{{ $g }}" @selected(old('groups.'.$team->id, $team->group_name) === $g)>Girone {{ $g }}</option>
                                @endforeach
                            </select>
                        </label>
                    @endforeach
                </div>
                <p class="hint">Se cambi il numero di gironi, salva e poi ricontrolla questa lista: le squadre di un girone che non c'è più tornano senza girone.</p>
            @endif
        @endif

        <label for="lineup_max_players">Giocatori in distinta al massimo</label>
        <input id="lineup_max_players" type="number" inputmode="numeric" min="1" max="40" name="lineup_max_players" value="{{ old('lineup_max_players', $tournament->lineup_max_players) }}" placeholder="{{ App\Models\Setting::lineupMaxPlayers() }}" style="max-width: 160px" @error('lineup_max_players') aria-invalid="true" @enderror>
        <p class="hint">Lascia vuoto per usare il valore delle Impostazioni ({{ App\Models\Setting::lineupMaxPlayers() }}, dal regolamento). Mettilo solo se questa categoria ha un limite diverso.</p>
        @include('partials.error', ['field' => 'lineup_max_players'])

        <label for="description">Descrizione</label>
        <textarea id="description" name="description" rows="3">{{ old('description', $tournament->description) }}</textarea>

        <div class="form-actions">
            <button class="btn" type="submit">Salva</button>
            <a class="btn secondary" href="{{ route('tournaments.index') }}">Annulla</a>
        </div>
    </form>

    <script>
        // Il numero di gironi serve solo se la formula è "A gironi".
        document.querySelectorAll('[data-format]').forEach(function (radio) {
            radio.addEventListener('change', function () {
                document.getElementById('groups-count-field').hidden = radio.value !== 'gironi';
            });
        });
    </script>
@endsection
