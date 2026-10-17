@extends('pdf.layout')

@section('title', 'Distinta di gara')
@section('eyebrow', $lineup->game ? $lineup->game->tournament->name.($lineup->game->matchday ? ' · '.$lineup->game->matchday.'ª giornata' : '') : $team->tournament->name)
@section('subtitle', $lineup->title())

@section('content')
    <table class="grid" style="margin-bottom: 12px">
        <tr>
            <th>Squadra</th><td>{{ $team->name }}</td>
            <th>Data e ora</th><td>{{ $lineup->kickoff_at?->format('d/m/Y H:i') ?? '' }}</td>
            <th>Campo</th><td>{{ $lineup->venue }}</td>
        </tr>
    </table>

    <table class="grid">
        <thead>
            <tr><th class="num">N°</th><th>Cognome e nome</th><th class="num">Anno</th><th class="num">C / VC</th><th style="width: 32%">Documento</th></tr>
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
                <tr><td colspan="5">Nessun convocato.</td></tr>
            @endforelse
        </tbody>
    </table>
    <p class="small muted">{{ $players->count() }} giocatori su {{ $max }} ammessi dal regolamento.</p>

    <div class="avoid">
        <h2>Allenatori e dirigenti</h2>
        <table class="grid">
            <thead>
                <tr><th>Cognome e nome</th><th>Ruolo</th><th style="width: 32%">Documento</th></tr>
            </thead>
            <tbody>
                @php($coaches = $team->coach_name ? collect([$team->coach_name]) : $team->coaches->pluck('name'))
                @foreach ($coaches as $coach)
                    <tr><td>{{ $coach }}</td><td>Allenatore</td><td class="write"></td></tr>
                @endforeach
                @for ($i = $coaches->count(); $i < 3; $i++)
                    <tr><td class="write"></td><td></td><td></td></tr>
                @endfor
            </tbody>
        </table>

        <table class="sign">
            <tr><td>Firma del dirigente responsabile</td><td class="gap"></td><td>Firma dell'arbitro</td></tr>
        </table>
    </div>
@endsection
