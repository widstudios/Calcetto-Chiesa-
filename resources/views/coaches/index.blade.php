@extends('layouts.app')

@section('title', 'Allenatori')

@section('content')
    <div class="page-head">
        <div>
            <span class="eyebrow">Account</span>
            <h1>Allenatori</h1>
        </div>
        <div class="actions">
            <a class="btn secondary" href="{{ route('users.index') }}"><x-icon name="key" /> Utenti e ruoli</a>
            <a class="btn" href="{{ route('coaches.create') }}"><x-icon name="plus" /> Nuovo allenatore</a>
        </div>
    </div>
    <p class="muted">Qui segui il tesseramento CSI degli allenatori e crei gli account con cui accedono per gestire la rosa della loro squadra.</p>

    @if ($coaches->isNotEmpty())
        @php($toRegister = $coaches->reject->isCsiRegistered())
        <div class="stats">
            <div class="stat">
                <span class="stat-icon"><x-icon name="coach" /></span>
                <div><div class="stat-value">{{ $coaches->count() - $toRegister->count() }}</div><div class="stat-label">Tesserati CSI</div></div>
            </div>
            <div class="stat">
                <span class="stat-icon gold"><x-icon name="coach" /></span>
                <div><div class="stat-value">{{ $toRegister->count() }}</div><div class="stat-label">Da tesserare</div></div>
            </div>
        </div>
    @endif

    <div class="card flush">
        @if ($coaches->isEmpty())
            <div class="empty"><x-icon name="coach" /><p>Nessun account allenatore.</p></div>
        @else
            <ul class="list">
                @foreach ($coaches as $coach)
                    <li class="list-item">
                        <span class="avatar">{{ collect(explode(' ', $coach->name))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->join('') }}</span>
                        <div class="list-main">
                            <div class="list-title">
                                {{ $coach->name }}
                                @if ($coach->isAdmin())
                                    <span class="badge gold">Anche admin</span>
                                @endif
                                @if ($coach->isCsiRegistered())
                                    <span class="badge">Tesserato CSI dal {{ $coach->csi_registered_on->format('d/m/Y') }}</span>
                                @else
                                    <span class="badge gold">Da tesserare</span>
                                @endif
                            </div>
                            <div class="list-meta">
                                <span><x-icon name="mail" /> {{ $coach->email }}</span>
                                <span><x-icon name="users" /> {{ implode(', ', $teamNames[$coach->id]) ?: 'Nessuna squadra' }}</span>
                            </div>
                        </div>
                        <div class="actions">
                            <a class="btn small secondary" href="{{ route('coaches.edit', $coach) }}"><x-icon name="pencil" /> <span class="btn-text">Modifica</span></a>
                            @include('partials.delete', ['action' => route('coaches.destroy', $coach), 'confirm' => $coach->isAdmin()
                                ? 'Togliere '.$coach->name.' dagli allenatori? Resta amministratore.'
                                : 'Eliminare l\'account di '.$coach->name.'?'])
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
