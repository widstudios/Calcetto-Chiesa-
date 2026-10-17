@extends('pdf.layout')

@section('title', 'Presenze '.$team->name)
@section('eyebrow', $team->tournament->name)
@section('subtitle', $dates->isEmpty() ? 'Nessun appello fatto' : $dates->count().' appelli, dal '.$dates->first()->format('d/m/Y').' al '.$dates->last()->format('d/m/Y'))

@section('content')
    <h2 style="margin-top: 0">Riepilogo per calciatore</h2>
    <table class="grid">
        <thead>
            <tr><th>Calciatore</th><th class="num">Anno</th><th class="num">Presenze</th><th class="num">Assenze</th><th class="num">Frequenza</th></tr>
        </thead>
        <tbody>
            @forelse ($summary as $row)
                <tr>
                    <td>{{ $row->player->last_name }} {{ $row->player->first_name }}</td>
                    <td class="num">{{ $row->player->birth_year }}</td>
                    <td class="num">{{ $row->present }}</td>
                    <td class="num">{{ $row->absent }}</td>
                    <td class="num">{{ $row->percent === null ? '–' : $row->percent.'%' }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Nessun calciatore in rosa.</td></tr>
            @endforelse
        </tbody>
    </table>

    {{-- Registro: una colonna per appello, a blocchi per stare nella larghezza del foglio. --}}
    @foreach ($dates->chunk(14) as $chunk)
        <div style="page-break-before: always"></div>
        <h2 style="margin-top: 0">Registro{{ $loop->count > 1 ? ' ('.$loop->iteration.' di '.$loop->count.')' : '' }}</h2>
        <table class="grid small">
            <thead>
                <tr>
                    <th>Calciatore</th>
                    @foreach ($chunk as $date)
                        <th class="num">{{ $date->format('d/m') }}</th>
                    @endforeach
                </tr>
            </thead>
            <tbody>
                @foreach ($summary as $row)
                    <tr>
                        <td>{{ $row->player->last_name }} {{ $row->player->first_name }}</td>
                        @foreach ($chunk as $date)
                            @php($mark = $marks->get($row->player->id.'|'.$date->toDateString()))
                            <td class="num">@if ($mark === true)<span class="yes">P</span>@elseif ($mark === false)<span class="no">A</span>@endif</td>
                        @endforeach
                    </tr>
                @endforeach
                <tr>
                    <th>Presenti</th>
                    @foreach ($chunk as $date)
                        <th class="num">{{ $presentByDate->get($date->toDateString(), 0) }}</th>
                    @endforeach
                </tr>
            </tbody>
        </table>
        <p class="small muted">P = presente, A = assente, vuoto = non segnato.</p>
    @endforeach
@endsection
