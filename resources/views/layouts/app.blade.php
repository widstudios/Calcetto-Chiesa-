<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title>@yield('title', 'Gestionale') · {{ \App\Models\Setting::siteName() }}</title>
    @if ($logo = \App\Models\Setting::logoUrl())
        <link rel="icon" href="{{ $logo }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700&family=Barlow:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) }}">
    @include('partials.theme')
</head>
@auth
    @php
        $navItems = auth()->user()->isAdmin()
            ? [
                ['dashboard', 'dashboard', 'Home', 'home'],
                ['tournaments.index', 'tournaments.*', 'Tornei', 'trophy'],
                ['teams.index', ['teams.index', 'teams.create', 'teams.show', 'teams.edit', 'teams.players.*'], 'Squadre', 'users'],
                ['coaches.index', 'coaches.*', 'Allenatori', 'coach'],
                ['calendar.index', ['calendar.*', 'games.index', 'games.create', 'games.edit', 'games.sheet.*'], 'Calendario', 'calendar'],
                ['referees.index', 'referees.*', 'Arbitri', 'whistle'],
                ['attendances.index', ['attendances.*', 'teams.attendances.*'], 'Presenze', 'checklist'],
                ['lineups.index', ['lineups.*', 'teams.lineups.*'], 'Distinte', 'clipboard'],
                ['drills.index', 'drills.*', 'Esercizi', 'cone'],
                ['documents.index', 'documents.*', 'Documenti', 'file'],
                ['imports.index', 'imports.*', 'Importa', 'upload'],
            ]
            : [
                ['dashboard', 'dashboard', 'Home', 'home'],
                ['teams.index', ['teams.index', 'teams.show', 'teams.players.*'], 'La mia rosa', 'shirt'],
                ['calendar.index', 'calendar.*', 'Calendario', 'calendar'],
                ['attendances.index', ['attendances.*', 'teams.attendances.*'], 'Presenze', 'checklist'],
                ['lineups.index', ['lineups.*', 'teams.lineups.*'], 'Distinte', 'clipboard'],
                ['drills.index', 'drills.*', 'Esercizi', 'cone'],
                ['documents.index', 'documents.*', 'Documenti', 'file'],
            ];
    @endphp
    <body @class(['has-nav', 'nav-wide' => count($navItems) > 6])>
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="{{ route('dashboard') }}" title="{{ \App\Models\Setting::siteName() }}">
                <x-brand-mark />
                <span class="brand-name">{{ \App\Models\Setting::siteName() }}</span>
            </a>
            <nav class="topnav" aria-label="Menu principale">
                @foreach ($navItems as [$route, $pattern, $label, $icon])
                    <a href="{{ route($route) }}" @class(['active' => request()->routeIs(...(array) $pattern)]) @if (request()->routeIs(...(array) $pattern)) aria-current="page" @endif>
                        <x-icon :name="$icon" /> {{ $label }}
                    </a>
                @endforeach
            </nav>
            {{-- Menu che scorre: la voce della pagina aperta resta visibile --}}
            <script>document.fonts.ready.then(() => document.querySelector('.topnav a.active')?.scrollIntoView({ block: 'nearest', inline: 'nearest' }));</script>
            <div class="user-box">
                <div class="user-chip">
                    <strong>{{ auth()->user()->name }}</strong>
                    <span>{{ auth()->user()->role->label() }}</span>
                </div>
                @if (auth()->user()->isAdmin())
                    <a @class(['icon-btn', 'active' => request()->routeIs('users.*')]) href="{{ route('users.index') }}" title="Utenti e ruoli" aria-label="Utenti e ruoli"><x-icon name="users" /></a>
                    <a @class(['icon-btn', 'active' => request()->routeIs('settings.*')]) href="{{ route('settings.edit') }}" title="Impostazioni (nome, logo e colori)" aria-label="Impostazioni"><x-icon name="settings" /></a>
                @endif
                <a @class(['icon-btn', 'active' => request()->routeIs('guide')]) href="{{ route('guide') }}" title="Guida all'app" aria-label="Guida all'app"><x-icon name="help" /></a>
                <a class="icon-btn" href="{{ route('password.edit') }}" title="Cambia password" aria-label="Cambia password"><x-icon name="key" /></a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button class="icon-btn" type="submit" title="Esci" aria-label="Esci"><x-icon name="logout" /></button>
                </form>
            </div>
        </div>
    </header>

    <main>
        @if (session('status'))
            <div class="flash" role="status"><x-icon name="check" /> {{ session('status') }}</div>
        @endif
        @yield('content')
    </main>

    <nav class="bottomnav" aria-label="Menu principale">
        @foreach ($navItems as [$route, $pattern, $label, $icon])
            <a href="{{ route($route) }}" @class(['active' => request()->routeIs(...(array) $pattern)]) @if (request()->routeIs(...(array) $pattern)) aria-current="page" @endif>
                <span class="icon-wrap"><x-icon :name="$icon" /></span>
                <span>{{ $label }}</span>
            </a>
        @endforeach
    </nav>
    </body>
@else
    <body>
    @yield('content')
    </body>
@endauth
</html>
