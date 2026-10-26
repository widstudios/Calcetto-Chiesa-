@extends('layouts.app')

@section('title', 'Arbitri')

@section('content')
    <div class="page-head">
        <div>
            <span class="eyebrow">Partite</span>
            <h1>Arbitri</h1>
        </div>
        <div class="actions">
            <a class="btn" href="{{ route('referees.create') }}"><x-icon name="plus" /> Nuovo arbitro</a>
        </div>
    </div>
    <p class="muted">Nome, cognome e telefono degli arbitri e le partite che dirigono. Il telefono lo vedono solo gli amministratori, mai il sito pubblico.</p>

    @if ($referees->isNotEmpty())
        <div class="stats">
            <div class="stat">
                <span class="stat-icon"><x-icon name="whistle" /></span>
                <div><div class="stat-value">{{ $referees->count() }}</div><div class="stat-label">Arbitri</div></div>
            </div>
            <div class="stat">
                <span class="stat-icon gold"><x-icon name="calendar" /></span>
                <div><div class="stat-value">{{ $unassigned }}</div><div class="stat-label">Partite da giocare senza arbitro</div></div>
            </div>
        </div>
    @endif

    <div class="card flush">
        @if ($referees->isEmpty())
            <div class="empty"><x-icon name="whistle" /><p>Nessun arbitro ancora.</p></div>
        @else
            <ul class="list">
                @foreach ($referees as $referee)
                    <li class="list-item">
                        <span class="avatar">{{ mb_substr($referee->first_name, 0, 1) }}{{ mb_substr($referee->last_name, 0, 1) }}</span>
                        <div class="list-main">
                            <div class="list-title">
                                {{ $referee->fullName() }}
                                <span class="badge">{{ $referee->games->count() === 1 ? '1 partita' : $referee->games->count().' partite' }}</span>
                            </div>
                            <div class="list-meta">
                                @if ($referee->phone)
                                    <a class="tel" href="tel:{{ \App\Models\Player::dialable($referee->phone) }}"><x-icon name="phone" /> {{ $referee->phone }}</a>
                                @else
                                    <span><x-icon name="phone" /> Telefono non inserito</span>
                                @endif
                            </div>
                            @if ($referee->games->isNotEmpty())
                                <ul class="referee-games">
                                    @foreach ($referee->games as $game)
                                        <li>
                                            <a href="{{ route('games.edit', $game) }}">{{ $game->homeTeam->name }} – {{ $game->awayTeam->name }}</a>
                                            <span class="muted">
                                                · {{ $game->kickoff_at?->format('d/m/Y H:i') ?? 'data da definire' }}
                                                @if ($game->matchday) · {{ $game->matchday }}ª giornata @endif
                                                · {{ $game->tournament->name }}
                                                @if ($game->isPlayed()) · giocata @endif
                                            </span>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                        <div class="actions">
                            <a class="btn small secondary" href="{{ route('referees.edit', $referee) }}"><x-icon name="pencil" /> <span class="btn-text">Modifica</span></a>
                            @include('partials.delete', ['action' => route('referees.destroy', $referee), 'confirm' => 'Eliminare l\'arbitro '.$referee->fullName().'? Le sue partite resteranno senza arbitro.'])
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
