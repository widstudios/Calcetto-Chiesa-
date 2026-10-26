@extends('pdf.layout')

@section('title', 'Classifica')
@section('eyebrow', $tournament->name.($tournament->season ? ' '.$tournament->season : ''))

@section('content')
    @if ($groupStandings ?? null)
        @foreach ($groupStandings as $group => $rows)
            <div class="avoid">
                <h2>{{ $group !== '' ? 'Girone '.$group : 'Senza girone' }}</h2>
                @include('pdf.standings-table', ['standings' => $rows])
            </div>
        @endforeach
    @else
        @include('pdf.standings-table', ['standings' => $standings])
    @endif

    @if ($scorers->isNotEmpty())
        <div class="avoid">
            <h2>Marcatori</h2>
            <table class="grid">
                <thead><tr><th class="num">#</th><th>Calciatore</th><th>Squadra</th><th class="num">Gol</th></tr></thead>
                <tbody>
                    @foreach ($scorers as $player)
                        <tr><td class="num">{{ $loop->iteration }}</td><td>{{ $player->fullName() }}</td><td>{{ $player->team->name }}</td><td class="num">{{ (int) $player->goals }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    @if ($ratings->isNotEmpty())
        <div class="avoid">
            <h2>Voti medi</h2>
            <table class="grid">
                <thead><tr><th class="num">#</th><th>Calciatore</th><th>Squadra</th><th class="num">Media</th><th class="num">Partite</th></tr></thead>
                <tbody>
                    @foreach ($ratings as $player)
                        <tr><td class="num">{{ $loop->iteration }}</td><td>{{ $player->fullName() }}</td><td>{{ $player->team->name }}</td><td class="num">{{ number_format($player->average_rating, 2, ',', '') }}</td><td class="num">{{ $player->rated_games }}</td></tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
