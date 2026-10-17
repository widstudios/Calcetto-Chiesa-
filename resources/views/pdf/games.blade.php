@extends('pdf.layout')

@section('title', 'Calendario e risultati')
@section('eyebrow', $tournament->name.($tournament->season ? ' '.$tournament->season : ''))
@section('subtitle', $games->count().' partite')

@section('content')
    @forelse ($games->groupBy(fn ($g) => $g->matchday ?? 0)->sortKeys() as $matchday => $day)
        <div class="avoid">
            <h2 @if ($loop->first) style="margin-top: 0" @endif>{{ $matchday ? $matchday.'ª giornata' : 'Senza giornata' }}</h2>
            <table class="grid">
                <thead>
                    <tr><th style="width: 18%">Data e ora</th><th>Casa</th><th class="num">Risultato</th><th>Ospiti</th><th style="width: 20%">Campo</th></tr>
                </thead>
                <tbody>
                    @foreach ($day as $game)
                        <tr>
                            <td>{{ $game->kickoff_at?->format('d/m/Y H:i') ?? 'Da definire' }}</td>
                            <td>{{ $game->homeTeam->name }}</td>
                            <td class="num">{{ $game->isPlayed() ? $game->home_score.' – '.$game->away_score : '–' }}</td>
                            <td>{{ $game->awayTeam->name }}</td>
                            <td>{{ $game->venue }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @empty
        <p>Nessuna partita in calendario.</p>
    @endforelse
@endsection
