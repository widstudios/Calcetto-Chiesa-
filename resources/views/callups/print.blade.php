<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Distinta {{ $team->name }} · {{ config('app.name') }}</title>
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) }}">
</head>
<body class="print-page">
    <div class="print-toolbar no-print">
        <a class="btn small secondary" href="{{ route('callups.index', ['tournament' => $game->tournament_id]) }}"><x-icon name="back" /> Distinte</a>
        <a class="btn small secondary" href="{{ route('games.callups.edit', [$game, $team]) }}"><x-icon name="pencil" /> Modifica</a>
        <button class="btn small" type="button" onclick="window.print()"><x-icon name="printer" /> Stampa</button>
    </div>
    @if (session('status'))
        <div class="flash no-print" role="status"><x-icon name="check" /> {{ session('status') }}</div>
    @endif

    <article class="distinta">
        <header>
            <p class="distinta-tournament">{{ $game->tournament->name }}@if ($game->matchday) · {{ $game->matchday }}ª giornata @endif</p>
            <h1>Distinta di gara</h1>
            <p class="distinta-match">{{ $game->homeTeam->name }} – {{ $game->awayTeam->name }}</p>
            <dl class="distinta-info">
                <div><dt>Squadra</dt><dd>{{ $team->name }}</dd></div>
                <div><dt>Data e ora</dt><dd>{{ $game->kickoff_at?->format('d/m/Y H:i') ?? '________________' }}</dd></div>
                <div><dt>Campo</dt><dd>{{ $game->venue ?: '________________' }}</dd></div>
            </dl>
        </header>

        <table class="distinta-table">
            <thead>
                <tr><th class="num">N°</th><th>Cognome e nome</th><th class="num">Anno</th><th></th><th>Documento</th></tr>
            </thead>
            <tbody>
                @forelse ($callups as $callup)
                    <tr>
                        <td class="num">{{ $callup->shirt_number }}</td>
                        <td>{{ $callup->player->last_name }} {{ $callup->player->first_name }}</td>
                        <td class="num">{{ $callup->player->birth_year }}</td>
                        <td class="num">{{ match ($callup->captain) { App\Models\Callup::CAPTAIN => 'C', App\Models\Callup::VICE => 'VC', default => '' } }}</td>
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
                @php($coaches = $team->coach_name ? collect([$team->coach_name]) : $team->coaches->pluck('name'))
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
