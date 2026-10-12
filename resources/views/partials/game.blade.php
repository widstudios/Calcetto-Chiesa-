{{-- Una partita: giornata, data, campo e tabellone con il risultato. --}}
<article class="game">
    <div class="game-top">
        <div class="list-meta">
            @if ($game->matchday)
                <span class="badge gold">{{ $game->matchday }}ª giornata</span>
            @endif
            @if ($game->kickoff_at)
                <span><x-icon name="clock" /> {{ $game->kickoff_at->format('d/m/Y · H:i') }}</span>
            @endif
            @if ($game->venue)
                <span><x-icon name="pin" /> {{ $game->venue }}</span>
            @endif
        </div>
        @if ($game->status === \App\Enums\GameStatus::Rinviata)
            <span class="badge grey">{{ $game->status->label() }}</span>
        @endif
    </div>
    <div class="match">
        <div class="team home">{{ $game->homeTeam->name }}</div>
        @if ($game->isPlayed())
            <div class="score" aria-label="Risultato {{ $game->home_score }} a {{ $game->away_score }}">{{ $game->home_score }} – {{ $game->away_score }}</div>
        @else
            <div class="score pending">VS</div>
        @endif
        <div class="team away">{{ $game->awayTeam->name }}</div>
    </div>
    @if ($editable ?? false)
        <div class="game-bottom">
            <div class="actions">
                <a class="btn small secondary" href="{{ route('games.edit', $game) }}"><x-icon name="pencil" /> {{ $game->isPlayed() ? 'Modifica' : 'Risultato' }}</a>
                @include('partials.delete', ['action' => route('games.destroy', $game), 'confirm' => 'Eliminare questa partita?'])
            </div>
        </div>
    @endif
</article>
