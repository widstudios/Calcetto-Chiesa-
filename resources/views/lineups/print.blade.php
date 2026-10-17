<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Distinta {{ $team->name }} · {{ \App\Models\Setting::siteName() }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) }}">
</head>
<body class="print-page">
    <div class="print-toolbar no-print">
        <a class="btn small secondary" href="{{ route('teams.lineups.index', $team) }}"><x-icon name="back" /> Distinte</a>
        <a class="btn small secondary" href="{{ route('teams.lineups.edit', [$team, $lineup]) }}"><x-icon name="pencil" /> Modifica</a>
        <a class="btn small secondary" href="{{ route('teams.lineups.pdf', [$team, $lineup]) }}"><x-icon name="download" /> PDF</a>
        <button class="btn small" type="button" onclick="window.print()"><x-icon name="printer" /> Stampa</button>
    </div>
    @if (session('status'))
        <div class="flash no-print" role="status"><x-icon name="check" /> {{ session('status') }}</div>
    @endif

    <article class="distinta">
        <header>
            @if ($logo = \App\Models\Setting::logoUrl())
                <img class="distinta-logo" src="{{ $logo }}" alt="">
            @endif
            @php($game = $lineup->game)
            @if ($game)
                <p class="distinta-tournament">{{ $game->tournament->name }}@if ($game->matchday) · {{ $game->matchday }}ª giornata @endif</p>
            @endif
            <h1>Distinta di gara</h1>
            <p class="distinta-match">{{ $lineup->title() }}</p>
            <dl class="distinta-info">
                <div><dt>Squadra</dt><dd><span class="team-line"><x-team-badge :team="$team" size="sm" /> {{ $team->name }}</span></dd></div>
                <div><dt>Data e ora</dt><dd>{{ $lineup->kickoff_at?->format('d/m/Y H:i') ?? '________________' }}</dd></div>
                <div><dt>Campo</dt><dd>{{ $lineup->venue ?: '________________' }}</dd></div>
            </dl>
        </header>

        <table class="distinta-table">
            <thead>
                <tr><th class="num">N°</th><th>Cognome e nome</th><th class="num">Anno</th><th></th><th>Documento</th></tr>
            </thead>
            <tbody>
                @forelse ($players as $row)
                    <tr>
                        <td class="num">{{ $row->shirt_number }}</td>
                        <td>{{ $row->player->last_name }} {{ $row->player->first_name }}</td>
                        <td class="num">{{ $row->player->birth_year }}</td>
                        <td class="num">{{ match ($row->captain) { App\Models\LineupPlayer::CAPTAIN => 'C', App\Models\LineupPlayer::VICE => 'VC', default => '' } }}</td>
                        <td class="write"></td>
                    </tr>
                @empty
                    <tr><td colspan="5">Nessun convocato: compila prima la distinta.</td></tr>
                @endforelse
            </tbody>
        </table>

        <h2>Allenatori e dirigenti</h2>
        <table class="distinta-table">
            <thead>
                <tr><th>Cognome e nome</th><th>Ruolo</th><th>Documento</th></tr>
            </thead>
            <tbody>
                @php($coaches = $lineup->team->coach_name ? collect([$lineup->team->coach_name]) : $lineup->team->coaches->pluck('name'))
                @foreach ($coaches as $coach)
                    <tr><td>{{ $coach }}</td><td>Allenatore</td><td class="write"></td></tr>
                @endforeach
                @for ($i = $coaches->count(); $i < 3; $i++)
                    <tr><td class="write"></td><td class="write"></td><td class="write"></td></tr>
                @endfor
            </tbody>
        </table>

        <div class="distinta-sign">
            <div>Firma del dirigente responsabile</div>
            <div>Firma dell'arbitro</div>
        </div>
    </article>
</body>
</html>
