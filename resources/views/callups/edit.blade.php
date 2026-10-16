@extends('layouts.app')

@section('title', 'Distinta '.$team->name)

@section('content')
    @include('partials.form-head', ['back' => route('callups.index', ['tournament' => $game->tournament_id]), 'eyebrow' => $game->tournament->name, 'title' => 'Distinta '.$team->name])

    <div class="card flush">
        @include('partials.game', ['game' => $game])
    </div>

    <form class="card form-card" method="POST" action="{{ route('games.callups.update', [$game, $team]) }}">
        @csrf
        @method('PUT')
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
                                <td><input type="radio" name="captain" value="{{ $player->id }}" @checked(old('captain', $saved->firstWhere('captain', App\Models\Callup::CAPTAIN)?->player_id) == $player->id) aria-label="{{ $player->fullName() }} capitano"></td>
                                <td><input type="radio" name="vice" value="{{ $player->id }}" @checked(old('vice', $saved->firstWhere('captain', App\Models\Callup::VICE)?->player_id) == $player->id) aria-label="{{ $player->fullName() }} vice capitano"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif

        <div class="form-actions">
            <button class="btn" type="submit"><x-icon name="check" /> Salva distinta</button>
            <a class="btn secondary" href="{{ route('callups.index', ['tournament' => $game->tournament_id]) }}">Annulla</a>
        </div>
    </form>
@endsection
