@extends('layouts.app')

@section('title', $game->exists ? 'Modifica partita' : 'Nuova partita')

@section('content')
    @include('partials.form-head', ['back' => route('games.index', ['tournament' => $tournament->id]), 'eyebrow' => $tournament->name, 'title' => $game->exists ? 'Modifica partita' : 'Nuova partita'])

    @php($teams = $tournament->teams()->orderBy('name')->get())
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
                        @foreach ($teams as $team)
                            <option value="{{ $team->id }}" @selected(old('home_team_id', $game->home_team_id) == $team->id)>{{ $team->name }}</option>
                        @endforeach
                    </select>
                    @include('partials.error', ['field' => 'home_team_id'])
                </div>
                <div>
                    <label for="away_team_id">Squadra ospite *</label>
                    <select id="away_team_id" name="away_team_id" required>
                        <option value=""></option>
                        @foreach ($teams as $team)
                            <option value="{{ $team->id }}" @selected(old('away_team_id', $game->away_team_id) == $team->id)>{{ $team->name }}</option>
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
                    <label for="kickoff_at">Data e ora</label>
                    <input id="kickoff_at" type="datetime-local" name="kickoff_at" value="{{ old('kickoff_at', $game->kickoff_at?->format('Y-m-d\TH:i')) }}">
                    @include('partials.error', ['field' => 'kickoff_at'])
                </div>
            </div>

            <label for="venue">Campo</label>
            <input id="venue" type="text" name="venue" value="{{ old('venue', $game->venue) }}">

            <label for="referee_id">Arbitro</label>
            @php($referees = \App\Models\Referee::orderBy('last_name')->orderBy('first_name')->get())
            <select id="referee_id" name="referee_id">
                <option value="">Da assegnare</option>
                @foreach ($referees as $referee)
                    <option value="{{ $referee->id }}" @selected(old('referee_id', $game->referee_id) == $referee->id)>{{ $referee->last_name }} {{ $referee->first_name }}</option>
                @endforeach
            </select>
            @include('partials.error', ['field' => 'referee_id'])
            @if ($referees->isEmpty())
                <p class="hint">Nessun arbitro ancora: aggiungili da <a href="{{ route('referees.create') }}">Arbitri</a>.</p>
            @endif

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

            <div class="form-actions">
                <button class="btn" type="submit">Salva</button>
                <a class="btn secondary" href="{{ route('games.index', ['tournament' => $tournament->id]) }}">Annulla</a>
            </div>
        </form>
    @endif
@endsection
