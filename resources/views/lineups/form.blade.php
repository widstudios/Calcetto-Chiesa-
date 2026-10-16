@extends('layouts.app')

@section('title', $lineup->exists ? 'Modifica distinta' : 'Nuova distinta')

@section('content')
    @include('partials.form-head', ['back' => route('teams.lineups.index', $team), 'eyebrow' => $team->name, 'title' => $lineup->exists ? 'Modifica distinta' : 'Nuova distinta'])

    <form class="card form-card" method="POST" action="{{ $lineup->exists ? route('teams.lineups.update', [$team, $lineup]) : route('teams.lineups.store', $team) }}">
        @csrf
        @if ($lineup->exists) @method('PUT') @endif

        <label for="game_id" style="margin-top: 0">Partita del calendario</label>
        <select id="game_id" name="game_id">
            <option value="">Nessuna (amichevole o altra gara)</option>
            @foreach ($games as $game)
                <option value="{{ $game->id }}" @selected(old('game_id', $lineup->game_id) == $game->id)>
                    {{ $game->homeTeam->name }} – {{ $game->awayTeam->name }}@if ($game->kickoff_at) · {{ $game->kickoff_at->format('d/m/Y H:i') }}@endif
                </option>
            @endforeach
        </select>
        <p class="hint">Facoltativa. Se scegli una partita e lasci vuoti i campi qui sotto, data, avversario e campo si prendono dal calendario.</p>
        @include('partials.error', ['field' => 'game_id'])

        <div class="grid">
            <div>
                <label for="kickoff_at">Data e ora</label>
                <input id="kickoff_at" type="datetime-local" name="kickoff_at" value="{{ old('kickoff_at', $lineup->kickoff_at?->format('Y-m-d\TH:i')) }}">
                @include('partials.error', ['field' => 'kickoff_at'])
            </div>
            <div>
                <label for="opponent">Avversario</label>
                <input id="opponent" type="text" name="opponent" value="{{ old('opponent', $lineup->opponent) }}">
                @include('partials.error', ['field' => 'opponent'])
            </div>
            <div>
                <label for="venue">Campo</label>
                <input id="venue" type="text" name="venue" value="{{ old('venue', $lineup->venue) }}">
                @include('partials.error', ['field' => 'venue'])
            </div>
        </div>

        <span class="legend" style="margin-top: 24px">Chi va in campo</span>
        <p class="hint" style="margin-top: 0">Spunta i convocati e controlla il numero di maglia. Scegli capitano (C) e vice (VC) tra i convocati.{{ $isNew ? ' Sono già spuntati i calciatori della prima squadra.' : '' }}</p>
        @include('partials.error', ['field' => 'players'])
        @include('partials.error', ['field' => 'captain'])
        @include('partials.error', ['field' => 'vice'])

        @if ($team->players->isEmpty())
            <div class="empty"><x-icon name="shirt" /><p>Nessun calciatore in rosa.</p></div>
        @else
            <div class="table-wrap">
                <table class="sheet callup-sheet">
                    <thead>
                        <tr><th>Convocato</th><th>Maglia</th><th title="Capitano">C</th><th title="Vice capitano">VC</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($team->players as $player)
                            @php($row = $saved->get($player->id))
                            @php($called = old("players.{$player->id}.called", $isNew ? $player->squad_role === App\Enums\SquadRole::Titolare : (bool) $row))
                            <tr>
                                <td>
                                    <label class="inline-check" style="margin: 0">
                                        <input type="checkbox" name="players[{{ $player->id }}][called]" value="1" @checked($called)>
                                        <span>
                                            {{ $player->fullName() }}
                                            @if ($player->squad_role === App\Enums\SquadRole::Panchina)<span class="badge bench">Panchina</span>@endif
                                        </span>
                                    </label>
                                </td>
                                <td>
                                    <input type="number" inputmode="numeric" min="1" max="99" name="players[{{ $player->id }}][shirt_number]" value="{{ old("players.{$player->id}.shirt_number", $row ? $row->shirt_number : $player->shirt_number) }}" aria-label="Maglia di {{ $player->fullName() }}">
                                </td>
                                <td><input type="radio" name="captain" value="{{ $player->id }}" @checked(old('captain', $saved->firstWhere('captain', App\Models\LineupPlayer::CAPTAIN)?->player_id) == $player->id) aria-label="{{ $player->fullName() }} capitano"></td>
                                <td><input type="radio" name="vice" value="{{ $player->id }}" @checked(old('vice', $saved->firstWhere('captain', App\Models\LineupPlayer::VICE)?->player_id) == $player->id) aria-label="{{ $player->fullName() }} vice capitano"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="form-actions">
            <button class="btn" type="submit"><x-icon name="check" /> Salva distinta</button>
            <a class="btn secondary" href="{{ route('teams.lineups.index', $team) }}">Annulla</a>
        </div>
    </form>
@endsection
