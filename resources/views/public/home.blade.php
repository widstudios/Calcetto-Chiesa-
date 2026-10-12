@extends('layouts.public')

@section('title', $tournament?->name ?? 'Torneo')

@section('hero')
    <section class="hero">
        <div class="hero-inner">
            <span class="eyebrow">{{ $tournament?->season ? 'Stagione '.$tournament->season : 'Torneo di calcetto' }}</span>
            <h1>{{ $tournament?->name ?? config('app.name') }}</h1>
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
                <div class="hero-stats">
                    <div><strong>{{ $totals['teams'] }}</strong><span>Squadre</span></div>
                    <div><strong>{{ $totals['played'] }}</strong><span>Partite giocate</span></div>
                    <div><strong>{{ $totals['goals'] }}</strong><span>Gol segnati</span></div>
                    <div><strong>{{ $totals['played'] ? number_format($totals['goals'] / $totals['played'], 1, ',', '') : '–' }}</strong><span>Gol a partita</span></div>
                </div>
                <nav class="hero-links" aria-label="Sezioni">
                    <a href="#classifica">Classifica</a>
                    <a href="#risultati">Risultati</a>
                    <a href="#marcatori">Marcatori</a>
                    <a href="#pagelle">Pagelle</a>
                    <a href="#calendario">Calendario</a>
                </nav>
            @endif
        </div>
    </section>
@endsection

@section('content')
    @if (! $tournament)
        <div class="card empty"><x-icon name="trophy" /><p>Il torneo sta per cominciare: torna presto!</p></div>
    @else
        <div class="public-grid">
            <section class="card flush" id="classifica">
                <div class="card-head"><h2><x-icon name="trophy" /> Classifica</h2></div>
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
                                        <td class="team-name">{{ $row->team->name }}</td>
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
                    <summary>{{ $day ? $day.'ª giornata' : 'Senza giornata' }} <span class="muted small">{{ $dayGames->filter->isPlayed()->count() }}/{{ $dayGames->count() }} giocate</span></summary>
                    @foreach ($dayGames as $game)
                        @include('partials.game', ['game' => $game, 'sheet' => true])
                    @endforeach
                </details>
            @empty
                <div class="empty"><p>Il calendario non è ancora pronto.</p></div>
            @endforelse
        </section>
    @endif
@endsection
