@extends('layouts.public')

@section('title', $tournament?->name ?? 'Torneo')

@section('hero')
    @php($next = $tournament ? $nextGames->first() : null)
    <section class="hero">
        <div @class(['hero-inner', 'has-next' => $next])>
            <div class="hero-main">
                <span class="eyebrow">{{ $tournament?->season ? 'Stagione '.$tournament->season : 'Torneo di calcetto' }}</span>
                <h1>{{ $tournament?->name ?? \App\Models\Setting::siteName() }}</h1>
                @if ($tournaments->count() > 1)
                    <form class="hero-select" method="GET" action="{{ route('home') }}">
                        <label class="sr-only" for="torneo">Scegli il torneo</label>
                        <select id="torneo" name="torneo" onchange="this.form.submit()">
                            @foreach ($tournaments as $t)
                                <option value="{{ $t->id }}" @selected($t->is($tournament))>{{ $t->name }}</option>
                            @endforeach
                        </select>
                        <noscript><button class="btn small accent" type="submit">Vai</button></noscript>
                    </form>
                @endif
                @if ($tournament)
                    <dl class="scoreboard">
                        <div><dt>Squadre</dt><dd>{{ $totals['teams'] }}</dd></div>
                        <div><dt>Giocate</dt><dd>{{ $totals['played'] }}</dd></div>
                        <div><dt>Gol</dt><dd>{{ $totals['goals'] }}</dd></div>
                        <div><dt>Gol a partita</dt><dd>{{ $totals['played'] ? number_format($totals['goals'] / $totals['played'], 1, ',', '') : '–' }}</dd></div>
                    </dl>
                @endif
            </div>

            @if ($next)
                <a class="next-match" href="#calendario">
                    <span class="next-label">Prossima partita @if ($next->stage)<span>· {{ $next->stageLabel() }}</span>@elseif ($next->matchday)<span>· {{ $next->matchday }}ª giornata</span>@endif</span>
                    <span class="next-teams">
                        <span class="next-team"><x-team-badge :team="$next->homeTeam" /><strong>{{ $next->homeTeam->name }}</strong></span>
                        <span class="next-vs">vs</span>
                        <span class="next-team"><x-team-badge :team="$next->awayTeam" /><strong>{{ $next->awayTeam->name }}</strong></span>
                    </span>
                    <span class="next-when">
                        @if ($next->status === \App\Enums\GameStatus::Rinviata)
                            <span><x-icon name="clock" /> Rinviata</span>
                        @elseif ($next->kickoff_at)
                            <span><x-icon name="calendar" /> {{ ucfirst($next->kickoff_at->locale('it')->translatedFormat('l j F')) }}</span>
                            <span><x-icon name="clock" /> {{ $next->kickoff_at->format('H:i') }}</span>
                        @else
                            <span><x-icon name="calendar" /> Data da definire</span>
                        @endif
                        @if ($next->venue)
                            <span><x-icon name="pin" /> {{ $next->venue }}</span>
                        @endif
                    </span>
                </a>
            @endif
        </div>
    </section>

    @if ($tournament)
        <nav class="section-nav" aria-label="Sezioni della pagina">
            <div class="section-nav-inner">
                <a href="#classifica">{{ $tournament->hasGroups() ? 'Gironi' : 'Classifica' }}</a>
                <a href="#risultati">Risultati</a>
                <a href="#marcatori">Marcatori</a>
                <a href="#pagelle">Pagelle</a>
                <a href="#portieri">Portieri</a>
                @if ($knockout->isNotEmpty())
                    <a href="#fase-finale">Fase finale</a>
                @endif
                <a href="#calendario">Calendario</a>
                <a href="#regole">Regole</a>
            </div>
        </nav>
    @endif
@endsection

@section('content')
    @if (! $tournament)
        <div class="card empty"><x-icon name="trophy" /><p>Il torneo sta per cominciare: torna presto!</p></div>
    @else
        <div class="public-grid">
            <section class="card flush" id="classifica">
                <div class="card-head"><h2><x-icon name="trophy" /> {{ $groupStandings ? 'Classifiche dei gironi' : 'Classifica' }}</h2><a class="btn small secondary" href="{{ route('standings.pdf', $tournament) }}"><x-icon name="download" /> PDF</a></div>
                @if ($standings->isEmpty())
                    <div class="empty"><p>Nessuna squadra iscritta.</p></div>
                @else
                    @if ($groupStandings)
                        @foreach ($groupStandings as $group => $rows)
                            <h3 class="group-title">{{ $group !== '' ? 'Girone '.$group : 'Senza girone' }}</h3>
                            @if ($rows->isEmpty())
                                <div class="empty"><p>Nessuna squadra in questo girone.</p></div>
                            @else
                                @include('public.standings-table', ['standings' => $rows])
                            @endif
                        @endforeach
                    @else
                        @include('public.standings-table', ['standings' => $standings])
                    @endif
                    <p class="small muted legend-line">3 punti a vittoria, 1 a pareggio. A parità di punti contano scontro diretto, differenza reti, gol fatti e gol subiti.@if ($groupStandings) Nella classifica di ogni girone contano solo le partite tra squadre dello stesso girone.@endif</p>
                @endif
            </section>

            <div>
                <section class="card flush" id="risultati">
                    <div class="card-head"><h2><x-icon name="ball" /> Ultimi risultati</h2></div>
                    @forelse ($latestResults as $game)
                        @include('partials.game', ['game' => $game, 'sheet' => true])
                    @empty
                        <div class="empty"><p>Ancora nessuna partita giocata.</p></div>
                    @endforelse
                </section>

                <section class="card flush">
                    <div class="card-head"><h2><x-icon name="calendar" /> Prossime partite</h2></div>
                    @forelse ($nextGames as $game)
                        @include('partials.game', ['game' => $game])
                    @empty
                        <div class="empty"><p>Nessuna partita in programma.</p></div>
                    @endforelse
                </section>
            </div>
        </div>

        <div class="leaderboards">
            <section class="card flush" id="marcatori">
                <div class="card-head"><h2><x-icon name="ball" /> Classifica marcatori</h2></div>
                @include('public.leaderboard', ['players' => $scorers, 'value' => fn ($p) => (int) $p->goals, 'unit' => 'gol', 'empty' => 'Nessun gol registrato.'])
            </section>
            <section class="card flush" id="pagelle">
                <div class="card-head"><h2><x-icon name="check" /> Media voto</h2></div>
                @include('public.leaderboard', ['players' => $ratings, 'value' => fn ($p) => number_format($p->average_rating, 2, ',', ''), 'note' => fn ($p) => $p->rated_games.' '.($p->rated_games == 1 ? 'voto' : 'voti'), 'empty' => 'Nessun voto ancora.'])
            </section>
            <section class="card flush" id="portieri">
                <div class="card-head"><h2><x-icon name="shirt" /> Miglior portiere</h2></div>
                @include('public.leaderboard', ['players' => $goalkeepers, 'value' => fn ($p) => number_format($p->average_conceded, 2, ',', ''), 'unit' => 'subiti a partita', 'note' => fn ($p) => $p->keeper_games.' '.($p->keeper_games == 1 ? 'partita' : 'partite').' · '.$p->goals_conceded.' gol subiti'.($p->clean_sheets ? ' · '.$p->clean_sheets.' a porta inviolata' : ''), 'empty' => 'Nessun portiere segnato nei tabellini.'])
            </section>
            <section class="card flush">
                <div class="card-head"><h2><x-icon name="trophy" /> Migliori in campo</h2></div>
                @include('public.leaderboard', ['players' => $mvps, 'value' => fn ($p) => $p->mvp_count, 'unit' => fn ($p) => $p->mvp_count == 1 ? 'volta' : 'volte', 'empty' => 'Nessun premio assegnato.'])
            </section>
        </div>

        @if ($knockout->isNotEmpty())
            <section class="card flush" id="fase-finale">
                <div class="card-head"><h2><x-icon name="trophy" /> Fase finale</h2></div>
                <div class="bracket">
                    @foreach ($knockout as $stage => $stageGames)
                        <div class="bracket-round">
                            <h3>{{ \App\Enums\GameStage::from($stage)->label() }}</h3>
                            @foreach ($stageGames->groupBy('bracket_slot') as $legs)
                                @php($legs = $legs->sortBy('leg')->values())
                                @php($first = $legs->first())
                                @php($winner = \App\Support\Knockout::tieWinner($legs))
                                @php($goals = \App\Support\Knockout::aggregate($legs))
                                @php($penaltyGame = $legs->last()->hasPenalties() ? $legs->last() : null)
                                <div class="bracket-game">
                                    @foreach ([$first->homeTeam, $first->awayTeam] as $team)
                                        <div @class(['bracket-team', 'winner' => $winner === $team->id, 'out' => $winner && $winner !== $team->id])>
                                            <span class="team-line"><x-team-badge :team="$team" size="sm" /> {{ $team->name }}</span>
                                            <span class="bracket-score">
                                                @if ($legs->contains->isPlayed())
                                                    @if ($legs->count() > 1)<small>{{ $legs->map(fn ($g) => $g->isPlayed() ? ($g->home_team_id === $team->id ? $g->home_score : $g->away_score) : '–')->join(' + ') }} =</small>@endif
                                                    {{ $goals[$team->id] ?? 0 }}
                                                    @if ($penaltyGame) <small>({{ $penaltyGame->home_team_id === $team->id ? $penaltyGame->home_penalties : $penaltyGame->away_penalties }})</small>@endif
                                                @else
                                                    –
                                                @endif
                                            </span>
                                        </div>
                                    @endforeach
                                    @php($nextLeg = $legs->first(fn ($g) => ! $g->isPlayed()))
                                    @if ($legs->count() > 1 || ($nextLeg && $nextLeg->kickoff_at))
                                        <div class="bracket-when">
                                            {{ $legs->count() > 1 ? 'Andata e ritorno' : '' }}
                                            @if ($nextLeg?->kickoff_at){{ $legs->count() > 1 ? ' · '.($nextLeg->leg === 2 ? 'ritorno' : 'andata').' ' : '' }}{{ $nextLeg->kickoff_at->format('d/m · H:i') }}@endif
                                        </div>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endforeach
                </div>
                @php($final = $knockout->get('finale'))
                @php($champion = $final ? \App\Support\Knockout::tieWinner($final) : null)
                @if ($champion)
                    <p class="legend-line champion"><x-icon name="trophy" /> Vince il torneo <strong>{{ $final->first()->home_team_id === $champion ? $final->first()->homeTeam->name : $final->first()->awayTeam->name }}</strong></p>
                @endif
            </section>
        @endif

        <section class="card flush" id="calendario">
            <div class="card-head"><h2><x-icon name="calendar" /> Calendario completo</h2></div>
            @forelse ($matchdays as $day => $dayGames)
                <details class="matchday" @if ($day === $openMatchday) open @endif>
                    @php($done = $dayGames->filter->isPlayed()->count())
                    <summary>
                        <span>{{ $day ? $day.'ª giornata' : 'Senza giornata' }}</span>
                        <span @class(['day-progress', 'complete' => $done === $dayGames->count()])>{{ $done }}/{{ $dayGames->count() }} giocate</span>
                    </summary>
                    @foreach ($dayGames as $game)
                        @include('partials.game', ['game' => $game, 'sheet' => true])
                    @endforeach
                </details>
            @empty
                <div class="empty"><p>Il calendario non è ancora pronto.</p></div>
            @endforelse
        </section>
    @endif

    @include($tournament?->hasOwnRules() ? 'public.regole-torneo' : 'public.regole')
@endsection
