@extends('layouts.app')

@section('title', 'Presenze '.$team->name)

@section('content')
    @include('partials.form-head', ['back' => route('attendances.index'), 'eyebrow' => $team->tournament->name, 'title' => 'Presenze '.$team->name])

    <section class="card flush">
        <div class="card-head">
            <h2><x-icon name="checklist" /> Appelli</h2>
            <span class="badge">{{ $sessions->whereNotNull('total')->count() }} fatti</span>
        </div>
        <p class="hint" style="padding: 0 20px">Qui trovi gli allenamenti delle ultime quattro settimane, in base agli orari inseriti nella pagina della squadra.</p>
        @if ($sessions->isEmpty())
            <div class="empty"><x-icon name="calendar" /><p>Nessun allenamento recente. {{ $team->trainings->isEmpty() ? 'Aggiungi gli orari di allenamento nella pagina della squadra.' : '' }}</p></div>
        @else
            <ul class="list" style="margin-top: 8px">
                @foreach ($sessions as $session)
                    @php($done = $session->total !== null)
                    <li class="list-item">
                        <span @class(['shirt', 'none' => ! $done]) aria-hidden="true"><x-icon :name="$done ? 'check' : 'clock'" /></span>
                        <div class="list-main">
                            <div class="list-title">{{ ucfirst($session->date->locale('it')->isoFormat('dddd D MMMM YYYY')) }}</div>
                            <div class="list-meta">
                                @foreach ($session->trainings as $training)
                                    <span><x-icon name="clock" /> {{ $training->timeRange() }} · {{ $training->field }}</span>
                                @endforeach
                                @if ($done)
                                    <span class="badge">{{ $session->present }} presenti su {{ $session->total }}</span>
                                @else
                                    <span class="badge gold">Appello da fare</span>
                                @endif
                            </div>
                        </div>
                        <div class="actions">
                            <a @class(['btn small', 'secondary' => $done]) href="{{ route('teams.attendances.edit', [$team, 'data' => $session->date->toDateString()]) }}">
                                <x-icon :name="$done ? 'pencil' : 'checklist'" /> <span class="btn-text">{{ $done ? 'Modifica' : 'Fai appello' }}</span>
                            </a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
        @error('data')<div style="padding: 0 20px 16px">@include('partials.error', ['field' => 'data'])</div>@enderror
    </section>

    <section class="card flush">
        <div class="card-head">
            <h2>Riepilogo per calciatore</h2>
        </div>
        @if ($summary->isEmpty())
            <div class="empty"><x-icon name="shirt" /><p>Nessun calciatore in rosa.</p></div>
        @else
            <div class="table-wrap">
                <table class="stack">
                    <thead>
                        <tr><th>Calciatore</th><th>Presenze</th><th>Assenze</th><th>Frequenza</th></tr>
                    </thead>
                    <tbody>
                        @foreach ($summary->sortByDesc(fn ($row) => $row->percent ?? -1) as $row)
                            <tr>
                                <td class="title">{{ $row->player->fullName() }}</td>
                                <td class="num" data-label="Presenze">{{ $row->present }}</td>
                                <td class="num" data-label="Assenze">{{ $row->absent }}</td>
                                <td class="num" data-label="Frequenza">{{ $row->percent === null ? '–' : $row->percent.'%' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
