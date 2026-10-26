@extends('layouts.app')

@section('title', $game->exists ? 'Modifica partita' : 'Nuova partita')

@section('content')
    @include('partials.form-head', ['back' => route('games.index', ['tournament' => $tournament->id]), 'eyebrow' => $tournament->name, 'title' => $game->exists ? 'Modifica partita' : 'Nuova partita'])

    @php($teams = $tournament->teams()->orderByRaw('group_name is null')->orderBy('group_name')->orderBy('name')->get())
    @if ($teams->count() < 2)
        <div class="card empty">
            <x-icon name="users" />
            <p>Servono almeno due squadre nel torneo per creare una partita.</p>
            <a class="btn" href="{{ route('teams.create', ['tournament' => $tournament->id]) }}">Aggiungi una squadra</a>
        </div>
    @else
        <form class="card form-card" method="POST" action="{{ $game->exists ? route('games.update', $game) : route('games.store') }}">
            @csrf
            @if ($game->exists) @method('PUT') @endif
            <input type="hidden" name="tournament_id" value="{{ $tournament->id }}">

            <div class="grid">
                <div>
                    <label for="home_team_id">Squadra di casa *</label>
                    <select id="home_team_id" name="home_team_id" required>
                        <option value=""></option>
                        @foreach ($teams->groupBy(fn ($t) => $t->groupLabel() ?? '') as $label => $groupTeams)
                            @if ($label !== '')<optgroup label="{{ $label }}">@endif
                            @foreach ($groupTeams as $team)
                                <option value="{{ $team->id }}" @selected(old('home_team_id', $game->home_team_id) == $team->id)>{{ $team->name }}</option>
                            @endforeach
                            @if ($label !== '')</optgroup>@endif
                        @endforeach
                    </select>
                    @include('partials.error', ['field' => 'home_team_id'])
                </div>
                <div>
                    <label for="away_team_id">Squadra ospite *</label>
                    <select id="away_team_id" name="away_team_id" required>
                        <option value=""></option>
                        @foreach ($teams->groupBy(fn ($t) => $t->groupLabel() ?? '') as $label => $groupTeams)
                            @if ($label !== '')<optgroup label="{{ $label }}">@endif
                            @foreach ($groupTeams as $team)
                                <option value="{{ $team->id }}" @selected(old('away_team_id', $game->away_team_id) == $team->id)>{{ $team->name }}</option>
                            @endforeach
                            @if ($label !== '')</optgroup>@endif
                        @endforeach
                    </select>
                    @include('partials.error', ['field' => 'away_team_id'])
                </div>
                <div>
                    <label for="matchday">Giornata</label>
                    <input id="matchday" type="number" inputmode="numeric" name="matchday" min="1" max="99" value="{{ old('matchday', $game->matchday) }}">
                    @include('partials.error', ['field' => 'matchday'])
                </div>
                <div>
                    <label for="stage">Fase</label>
                    <select id="stage" name="stage">
                        <option value="">{{ $tournament->hasGroups() ? 'Gironi' : 'Campionato' }}</option>
                        @foreach (App\Enums\GameStage::cases() as $stage)
                            <option value="{{ $stage->value }}" @selected(old('stage', $game->stage?->value) === $stage->value)>{{ $stage->label() }}</option>
                        @endforeach
                    </select>
                    @include('partials.error', ['field' => 'stage'])
                </div>
                <div>
                    <label for="kickoff_at">Data e ora</label>
                    <input id="kickoff_at" type="datetime-local" name="kickoff_at" value="{{ old('kickoff_at', $game->kickoff_at?->format('Y-m-d\TH:i')) }}">
                    @include('partials.error', ['field' => 'kickoff_at'])
                </div>
            </div>

            <label for="venue">Campo</label>
            <input id="venue" type="text" name="venue" value="{{ old('venue', $game->venue) }}">

            <div class="grid">
                <div>
                    <label for="status">Stato *</label>
                    <select id="status" name="status" required>
                        @foreach (App\Enums\GameStatus::cases() as $status)
                            <option value="{{ $status->value }}" @selected(old('status', $game->status?->value) === $status->value)>{{ $status->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="home_score">Gol casa</label>
                    <input id="home_score" type="number" inputmode="numeric" name="home_score" min="0" max="99" value="{{ old('home_score', $game->home_score) }}">
                    @include('partials.error', ['field' => 'home_score'])
                </div>
                <div>
                    <label for="away_score">Gol ospiti</label>
                    <input id="away_score" type="number" inputmode="numeric" name="away_score" min="0" max="99" value="{{ old('away_score', $game->away_score) }}">
                    @include('partials.error', ['field' => 'away_score'])
                </div>
            </div>
            <p class="hint">Il risultato viene salvato solo se lo stato è "Giocata".</p>

            <div class="grid">
                <div>
                    <label for="home_penalties">Rigori casa</label>
                    <input id="home_penalties" type="number" inputmode="numeric" name="home_penalties" min="0" max="99" value="{{ old('home_penalties', $game->home_penalties) }}" @error('home_penalties') aria-invalid="true" @enderror>
                    @include('partials.error', ['field' => 'home_penalties'])
                </div>
                <div>
                    <label for="away_penalties">Rigori ospiti</label>
                    <input id="away_penalties" type="number" inputmode="numeric" name="away_penalties" min="0" max="99" value="{{ old('away_penalties', $game->away_penalties) }}">
                </div>
            </div>
            <p class="hint">Solo per la fase finale, se la partita finisce in parità: serve sapere chi passa il turno.</p>

            <div class="form-actions">
                <button class="btn" type="submit">Salva</button>
                <a class="btn secondary" href="{{ route('games.index', ['tournament' => $tournament->id]) }}">Annulla</a>
            </div>
        </form>
    @endif
@endsection
