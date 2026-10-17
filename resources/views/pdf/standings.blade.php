@extends('pdf.layout')

@section('title', 'Classifica')
@section('eyebrow', $tournament->name.($tournament->season ? ' '.$tournament->season : ''))

@section('content')
    <table class="grid">
        <thead>
            <tr><th class="num">#</th><th>Squadra</th><th class="num">Pt</th><th class="num">G</th><th class="num">V</th><th class="num">N</th><th class="num">P</th><th class="num">GF</th><th class="num">GS</th><th class="num">DR</th></tr>
        </thead>
        <tbody>
            @forelse ($standings as $row)
                <tr>
                    <td class="num">{{ $loop->iteration }}</td>
                    <td>{{ $row->team->name }}</td>
                    <td class="num"><strong>{{ $row->points }}</strong></td>
                    <td class="num">{{ $row->played }}</td>
                    <td class="num">{{ $row->won }}</td>
                    <td class="num">{{ $row->drawn }}</td>
                    <td class="num">{{ $row->lost }}</td>
                    <td class="num">{{ $row->goals_for }}</td>
                    <td class="num">{{ $row->goals_against }}</td>
                    <td class="num">{{ $row->goal_difference > 0 ? '+' : '' }}{{ $row->goal_difference }}</td>
                </tr>
            @empty
                <tr><td colspan="10">Nessuna squadra.</td></tr>
            @endforelse
        </tbody>
    </table>

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
