@extends('layouts.app')

@section('title', 'Presenze '.$team->name)

@section('content')
    @include('partials.form-head', ['back' => route('attendances.index'), 'eyebrow' => $team->tournament->name, 'title' => 'Presenze '.$team->name])

    <section class="card flush">
        <div class="card-head">
            <h2><x-icon name="checklist" /> Appello</h2>
            <span class="badge">{{ $trainings->count() }} a settimana</span>
        </div>
        @if ($trainings->isEmpty())
            <div class="empty"><x-icon name="calendar" /><p>Nessun orario di allenamento. Aggiungilo nella pagina della squadra per fare l'appello.</p></div>
        @else
            <ul class="list" style="margin-top: 8px">
                @foreach ($trainings as $row)
                    <li class="list-item">
                        <span @class(['shirt', 'none' => ! $row->done]) aria-hidden="true"><x-icon :name="$row->done ? 'check' : 'calendar'" /></span>
                        <div class="list-main">
                            <div class="list-title">{{ $row->training->weekdayName() }} · {{ $row->training->timeRange() }}</div>
                            <div class="list-meta">
                                <span>Campo: {{ $row->training->field }}</span>
                                <span>{{ $row->date->isToday() ? 'Oggi' : $row->date->format('d/m/Y') }}</span>
                                @if ($row->done)
                                    <span class="badge">{{ $row->done->present }} presenti su {{ $row->done->total }}</span>
                                @else
                                    <span class="badge gold">Appello da fare</span>
                                @endif
                            </div>
                        </div>
                        <div class="actions">
                            <a @class(['btn small', 'secondary' => $row->done]) href="{{ route('teams.attendances.edit', [$team, 'data' => $row->date->toDateString()]) }}">
                                <x-icon :name="$row->done ? 'pencil' : 'checklist'" /> <span class="btn-text">{{ $row->done ? 'Modifica' : 'Fai appello' }}</span>
                            </a>
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
        @error('data')<div style="padding: 0 20px 16px">@include('partials.error', ['field' => 'data'])</div>@enderror
    </section>

    @if ($history->isNotEmpty())
        <section class="card flush">
            <div class="card-head">
                <h2>Appelli fatti</h2>
                <span class="badge">{{ $history->count() }}</span>
            </div>
            <ul class="list" style="margin-top: 8px">
                @foreach ($history as $row)
                    <li class="list-item">
                        <span class="shirt" aria-hidden="true"><x-icon name="check" /></span>
                        <div class="list-main">
                            <div class="list-title">{{ ucfirst($row->date->locale('it')->isoFormat('dddd D MMMM YYYY')) }}</div>
                            <div class="list-meta"><span class="badge">{{ $row->done->present }} presenti su {{ $row->done->total }}</span></div>
                        </div>
                        <div class="actions">
                            <a class="btn small secondary" href="{{ route('teams.attendances.edit', [$team, 'data' => $row->date->toDateString()]) }}"><x-icon name="pencil" /> <span class="btn-text">Modifica</span></a>
                        </div>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif

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
