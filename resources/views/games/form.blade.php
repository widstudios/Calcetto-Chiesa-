@extends('layouts.app')

@section('title', $game->exists ? 'Modifica partita' : 'Nuova partita')

@section('content')
    <h1>{{ $game->exists ? 'Modifica partita' : 'Nuova partita' }}</h1>
    <p class="muted">{{ $tournament->name }}</p>

    @php($teams = $tournament->teams()->orderBy('name')->get())
    @if ($teams->count() < 2)
        <div class="card">
            <p>Servono almeno due squadre nel torneo per creare una partita.</p>
            <a class="btn" href="{{ route('teams.create', ['tournament' => $tournament->id]) }}">Aggiungi una squadra</a>
        </div>
    @else
        <form class="card" method="POST" action="{{ $game->exists ? route('games.update', $game) : route('games.store') }}">
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
                    @error('home_team_id') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label for="away_team_id">Squadra ospite *</label>
                    <select id="away_team_id" name="away_team_id" required>
                        <option value=""></option>
                        @foreach ($teams as $team)
                            <option value="{{ $team->id }}" @selected(old('away_team_id', $game->away_team_id) == $team->id)>{{ $team->name }}</option>
                        @endforeach
                    </select>
                    @error('away_team_id') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label for="matchday">Giornata</label>
                    <input id="matchday" type="number" name="matchday" min="1" max="99" value="{{ old('matchday', $game->matchday) }}">
                    @error('matchday') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label for="kickoff_at">Data e ora</label>
                    <input id="kickoff_at" type="datetime-local" name="kickoff_at" value="{{ old('kickoff_at', $game->kickoff_at?->format('Y-m-d\TH:i')) }}">
                    @error('kickoff_at') <div class="error">{{ $message }}</div> @enderror
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
                    <input id="home_score" type="number" name="home_score" min="0" max="99" value="{{ old('home_score', $game->home_score) }}">
                    @error('home_score') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div>
                    <label for="away_score">Gol ospiti</label>
                    <input id="away_score" type="number" name="away_score" min="0" max="99" value="{{ old('away_score', $game->away_score) }}">
                    @error('away_score') <div class="error">{{ $message }}</div> @enderror
                </div>
            </div>
            <p class="muted" style="font-size: 14px">Il risultato viene salvato solo se lo stato è "Giocata".</p>

            <button class="btn" type="submit">Salva</button>
            <a class="btn secondary" href="{{ route('games.index', ['tournament' => $tournament->id]) }}">Annulla</a>
        </form>
    @endif
@endsection
