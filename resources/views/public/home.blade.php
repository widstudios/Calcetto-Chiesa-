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
                    <span class="next-label">Prossima partita @if ($next->matchday)<span>· {{ $next->matchday }}ª giornata</span>@endif</span>
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
                <a href="#classifica">Classifica</a>
                <a href="#risultati">Risultati</a>
                <a href="#marcatori">Marcatori</a>
                <a href="#pagelle">Pagelle</a>
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
                <div class="card-head"><h2><x-icon name="trophy" /> Classifica</h2><a class="btn small secondary" href="{{ route('standings.pdf', $tournament) }}"><x-icon name="download" /> PDF</a></div>
                @if ($standings->isEmpty())
                    <div class="empty"><p>Nessuna squadra iscritta.</p></div>
                @else
                    <div class="table-wrap">
                        <table class="standings">
                            <thead>
                                <tr>
                                    <th class="pos">#</th><th>Squadra</th><th class="num" title="Punti">Pt</th>
                                    <th class="num" title="Giocate">G</th><th class="num" title="Vinte">V</th><th class="num" title="Pareggiate">N</th><th class="num" title="Perse">P</th>
                                    <th class="num hide-sm" title="Gol fatti">GF</th><th class="num hide-sm" title="Gol subiti">GS</th><th class="num" title="Differenza reti">DR</th>
                                    <th class="hide-sm">Forma</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($standings as $row)
                                    <tr @class(['leader' => $loop->first && $row->played])>
                                        <td class="pos">{{ $loop->iteration }}</td>
                                        <td class="team-name"><span class="team-line"><x-team-badge :team="$row->team" size="sm" /> {{ $row->team->name }}</span></td>
                                        <td class="num points">{{ $row->points }}</td>
                                        <td class="num">{{ $row->played }}</td>
                                        <td class="num">{{ $row->won }}</td>
                                        <td class="num">{{ $row->drawn }}</td>
                                        <td class="num">{{ $row->lost }}</td>
                                        <td class="num hide-sm">{{ $row->goals_for }}</td>
                                        <td class="num hide-sm">{{ $row->goals_against }}</td>
                                        <td class="num">{{ $row->goal_difference > 0 ? '+' : '' }}{{ $row->goal_difference }}</td>
                                        <td class="hide-sm"><span class="form">@foreach ($row->form as $r)<span class="form-{{ $r }}" title="{{ ['V' => 'Vinta', 'N' => 'Pareggiata', 'P' => 'Persa'][$r] }}">{{ $r }}</span>@endforeach</span></td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    <p class="small muted legend-line">3 punti a vittoria, 1 a pareggio. A parità di punti conta la differenza reti.</p>
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
            <section class="card flush">
                <div class="card-head"><h2><x-icon name="trophy" /> Migliori in campo</h2></div>
                @include('public.leaderboard', ['players' => $mvps, 'value' => fn ($p) => $p->mvp_count, 'unit' => fn ($p) => $p->mvp_count == 1 ? 'volta' : 'volte', 'empty' => 'Nessun premio assegnato.'])
            </section>
        </div>

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

    @include('public.regole')
@endsection
