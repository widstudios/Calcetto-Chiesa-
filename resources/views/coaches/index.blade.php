@extends('layouts.app')

@section('title', 'Allenatori')

@section('content')
    <div class="page-head">
        <div>
            <span class="eyebrow">Account</span>
            <h1>Allenatori</h1>
        </div>
        <a class="btn" href="{{ route('coaches.create') }}"><x-icon name="plus" /> Nuovo allenatore</a>
    </div>
    <p class="muted">Qui crei gli account con cui gli allenatori accedono per gestire la rosa della loro squadra. Se un allenatore non deve accedere, basta scrivere il suo nome nella scheda della squadra.</p>

    <div class="card flush">
        @if ($coaches->isEmpty())
            <div class="empty"><x-icon name="coach" /><p>Nessun account allenatore.</p></div>
        @else
            <ul class="list">
                @foreach ($coaches as $coach)
                    <li class="list-item">
                        <span class="avatar">{{ collect(explode(' ', $coach->name))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->join('') }}</span>
                        <div class="list-main">
                            <div class="list-title">{{ $coach->name }}</div>
                            <div class="list-meta">
                                <span><x-icon name="mail" /> {{ $coach->email }}</span>
                                <span><x-icon name="users" /> {{ $coach->teams->pluck('name')->join(', ') ?: 'Nessuna squadra' }}</span>
                            </div>
                        </div>
                        <div class="actions">
                            <a class="btn small secondary" href="{{ route('coaches.edit', $coach) }}"><x-icon name="pencil" /> <span class="btn-text">Modifica</span></a>
                            @include('partials.delete', ['action' => route('coaches.destroy', $coach), 'confirm' => 'Eliminare l\'account di '.$coach->name.'?'])
                        </div>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
@endsection
