@extends('layouts.app')

@section('title', 'Distinte '.$team->name)

@section('content')
    <div class="page-head">
        <div>
            <a class="small" href="{{ route('lineups.index') }}" style="display: inline-flex; align-items: center; gap: 4px; min-height: 44px"><x-icon name="back" /> Indietro</a>
            <span class="eyebrow" style="display: block">{{ $team->tournament->name }}</span>
            <h1>Distinte {{ $team->name }}</h1>
        </div>
        <a class="btn" href="{{ route('teams.lineups.create', $team) }}"><x-icon name="plus" /> Nuova distinta</a>
    </div>

    <div class="card flush">
        @if ($lineups->isEmpty())
            <div class="empty"><x-icon name="clipboard" /><p>Nessuna distinta. Crea la prima scegliendo chi va in campo.</p></div>
        @else
            <ul class="list">
                @foreach ($lineups as $lineup)
                    <li class="list-item">
                        <span class="shirt none" aria-hidden="true"><x-icon name="clipboard" /></span>
                        <div class="list-main">
                            <a class="list-title" href="{{ route('teams.lineups.edit', [$team, $lineup]) }}">{{ $lineup->title() }}</a>
                            <div class="list-meta">
                                @if ($lineup->kickoff_at)
                                    <span><x-icon name="clock" /> {{ $lineup->kickoff_at->format('d/m/Y · H:i') }}</span>
                                @endif
                                @if ($lineup->venue)
                                    <span><x-icon name="pin" /> {{ $lineup->venue }}</span>
                                @endif
                                @if ($lineup->game?->matchday)
                                    <span class="badge gold">{{ $lineup->game->matchday }}ª giornata</span>
                                @endif
                                <span class="badge">{{ $lineup->players_count }} convocati</span>
                            </div>
                        </div>
                        <div class="actions">
                            <a class="btn small" href="{{ route('teams.lineups.print', [$team, $lineup]) }}"><x-icon name="printer" /> <span class="btn-text">Stampa</span></a>
                            <a class="btn small secondary" href="{{ route('teams.lineups.pdf', [$team, $lineup]) }}"><x-icon name="download" /> <span class="btn-text">PDF</span></a>
                            <a class="btn small secondary" href="{{ route('teams.lineups.edit', [$team, $lineup]) }}"><x-icon name="pencil" /> <span class="btn-text">Modifica</span></a>
                            @include('partials.delete', ['action' => route('teams.lineups.destroy', [$team, $lineup]), 'confirm' => 'Eliminare questa distinta?'])
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
