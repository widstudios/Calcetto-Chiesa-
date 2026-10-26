@extends('layouts.app')

@section('title', 'Tabellino')

@section('content')
    @include('partials.form-head', ['back' => route('games.index', ['tournament' => $game->tournament_id]), 'eyebrow' => $game->tournament->name, 'title' => 'Tabellino'])

    <div class="card flush">
        @include('partials.game', ['game' => $game])
    </div>

    <form class="card form-card" method="POST" action="{{ route('games.sheet.update', $game) }}">
        @csrf
        @method('PUT')
        <p class="hint" style="margin-top: 0">Scrivi i gol e il voto (da 1 a 10, anche mezzi voti come 6.5) di chi ha giocato. Lascia vuoto chi non ha giocato. Con i pallini scegli il portiere di ogni squadra (serve per la classifica del miglior portiere) e il migliore in campo.</p>
        @include('partials.error', ['field' => 'mvp_player_id'])

        @foreach ([$game->homeTeam, $game->awayTeam] as $team)
            @php($goals = $game->performances->whereIn('player_id', $team->players->pluck('id'))->sum('goals'))
            @php($keeper = $game->performances->whereIn('player_id', $team->players->pluck('id'))->firstWhere('goalkeeper', true))
            <div class="card-head" style="margin-top: 20px">
                <h2>{{ $team->name }}</h2>
                <span class="muted small">Gol nel tabellino: {{ $goals }} su {{ $team->is($game->homeTeam) ? $game->home_score : $game->away_score }}</span>
            </div>
            @if ($team->players->isEmpty())
                <p class="muted">Nessun calciatore in rosa.</p>
            @else
                <div class="table-wrap">
                    <table class="sheet">
                        <thead>
                            <tr><th>Calciatore</th><th>Gol</th><th>Voto</th><th>Portiere</th><th>Migliore</th></tr>
                        </thead>
                        <tbody>
                            @foreach ($team->players->sortBy(fn ($p) => sprintf('%d-%03d', $p->squad_role === App\Enums\SquadRole::Panchina, $p->shirt_number ?? 999)) as $player)
                                @php($perf = $performances->get($player->id))
                                <tr>
                                    <td>
                                        <span class="muted">{{ $player->shirt_number ?? '–' }}</span>
                                        {{ $player->fullName() }}
                                        @if ($player->squad_role === App\Enums\SquadRole::Panchina)<span class="badge bench">Panchina</span>@endif
                                    </td>
                                    <td>
                                        <input type="number" inputmode="numeric" min="0" max="30" name="players[{{ $player->id }}][goals]" value="{{ old("players.{$player->id}.goals", $perf?->goals ?: '') }}" aria-label="Gol di {{ $player->fullName() }}">
                                    </td>
                                    <td>
                                        <input type="number" inputmode="decimal" min="1" max="10" step="0.5" name="players[{{ $player->id }}][rating]" value="{{ old("players.{$player->id}.rating", $perf?->rating) }}" aria-label="Voto di {{ $player->fullName() }}">
                                    </td>
                                    <td>
                                        <input type="radio" name="goalkeepers[{{ $team->id }}]" value="{{ $player->id }}" @checked(old("goalkeepers.{$team->id}", $keeper?->player_id) == $player->id) aria-label="{{ $player->fullName() }} in porta">
                                    </td>
                                    <td>
                                        <input type="radio" name="mvp_player_id" value="{{ $player->id }}" @checked(old('mvp_player_id', $game->mvp_player_id) == $player->id) aria-label="{{ $player->fullName() }} migliore in campo">
                                    </td>
                                </tr>
                                @error("players.{$player->id}.*")<tr><td colspan="5">@include('partials.error', ['field' => "players.{$player->id}.goals"])@include('partials.error', ['field' => "players.{$player->id}.rating"])</td></tr>@enderror
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <label class="inline-check" style="margin-top: 8px"><input type="radio" name="goalkeepers[{{ $team->id }}]" value="" @checked(! old("goalkeepers.{$team->id}", $keeper?->player_id))> Portiere non indicato</label>
            @endif
            @include('partials.error', ['field' => "goalkeepers.{$team->id}"])
        @endforeach

        <label class="inline-check"><input type="radio" name="mvp_player_id" value="" @checked(! old('mvp_player_id', $game->mvp_player_id))> Nessun migliore in campo</label>

        <div class="form-actions">
            <button class="btn" type="submit">Salva tabellino</button>
            <a class="btn secondary" href="{{ route('games.index', ['tournament' => $game->tournament_id]) }}">Annulla</a>
        </div>
    </form>
@endsection
