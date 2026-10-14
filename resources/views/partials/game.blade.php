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
    @php($result = $game->isPlayed() ? $game->home_score <=> $game->away_score : null)
    <div class="match">
        <div @class(['team home', 'lost' => $result === -1])>{{ $game->homeTeam->name }}</div>
        @if ($game->isPlayed())
            <div class="score" aria-label="Risultato {{ $game->home_score }} a {{ $game->away_score }}">{{ $game->home_score }} – {{ $game->away_score }}</div>
        @else
            <div class="score pending">VS</div>
        @endif
        <div @class(['team away', 'lost' => $result === 1])>{{ $game->awayTeam->name }}</div>
    </div>
    @if (($sheet ?? false) && $game->isPlayed() && ($game->performances->isNotEmpty() || $game->mvp))
        <div class="game-sheet">
            @foreach ([$game->home_team_id, $game->away_team_id] as $side => $teamId)
                <ul class="scorers {{ $side ? 'away' : 'home' }}">
                    @foreach ($game->performances->where('goals', '>', 0)->filter(fn ($p) => $p->player?->team_id === $teamId) as $perf)
                        <li><x-icon name="ball" /> {{ $perf->player->fullName() }}@if ($perf->goals > 1) ({{ $perf->goals }})@endif</li>
                    @endforeach
                </ul>
            @endforeach
            @if ($game->mvp)
                <div class="mvp"><span class="badge gold"><x-icon name="trophy" /> Migliore in campo: {{ $game->mvp->fullName() }}</span></div>
            @endif
        </div>
    @endif
    @if ($editable ?? false)
        <div class="game-bottom">
            <div class="actions">
                <a class="btn small secondary" href="{{ route('games.edit', $game) }}"><x-icon name="pencil" /> {{ $game->isPlayed() ? 'Modifica' : 'Risultato' }}</a>
                @if ($game->isPlayed())
                    <a class="btn small secondary" href="{{ route('games.sheet.edit', $game) }}"><x-icon name="ball" /> Tabellino</a>
                @endif
                @include('partials.delete', ['action' => route('games.destroy', $game), 'confirm' => 'Eliminare questa partita?'])
            </div>
        </div>
    @endif
</article>
