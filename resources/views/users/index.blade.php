@extends('layouts.app')

@section('title', 'Utenti e ruoli')

@section('content')
    <div class="page-head">
        <div>
            <span class="eyebrow">Account</span>
            <h1>Utenti e ruoli</h1>
        </div>
        <a class="btn" href="{{ route('users.create') }}"><x-icon name="plus" /> Nuovo utente</a>
    </div>
    <p class="muted">Gli amministratori gestiscono tutto il torneo; gli allenatori solo le loro squadre; gli arbitri vedono soltanto le partite che arbitrano (il loro accesso si dà dalla pagina Arbitri). Per nominare un altro admin apri Modifica e cambia il ruolo; un admin può essere anche allenatore delle sue squadre. Non puoi toglierti da solo il ruolo di admin: deve farlo un altro amministratore.</p>

    @include('partials.error', ['field' => 'user'])

    <div class="card flush">
        <ul class="list">
            @foreach ($users as $user)
                <li class="list-item">
                    <span class="avatar">{{ collect(explode(' ', $user->name))->map(fn ($w) => mb_substr($w, 0, 1))->take(2)->join('') }}</span>
                    <div class="list-main">
                        <div class="list-title">
                            {{ $user->name }}
                            <span @class(['badge', 'gold' => $user->isAdmin()])>{{ $user->roleLabel() }}</span>
                            @if ($user->is(auth()->user()))
                                <span class="muted">(tu)</span>
                            @endif
                        </div>
                        <div class="list-meta">
                            <span><x-icon name="mail" /> {{ $user->email }}</span>
                        </div>
                    </div>
                    <div class="actions">
                        <a class="btn small secondary" href="{{ $user->isReferee() && $user->referee ? route('referees.edit', $user->referee) : route('users.edit', $user) }}"><x-icon name="pencil" /> <span class="btn-text">Modifica</span></a>
                        @unless ($user->is(auth()->user()))
                            @include('partials.delete', ['action' => route('users.destroy', $user), 'confirm' => 'Eliminare l\'account di '.$user->name.'?'])
                        @endunless
                    </div>
                </li>
            @endforeach
        </ul>
    </div>
@endsection
