<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#052e16">
    <title>@yield('title', 'Gestionale') · {{ config('app.name') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700&family=Barlow:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) }}">
</head>
@auth
    @php
        $navItems = auth()->user()->isAdmin()
            ? [
                ['dashboard', 'dashboard', 'Home', 'home'],
                ['tournaments.index', 'tournaments.*', 'Tornei', 'trophy'],
                ['teams.index', ['teams.index', 'teams.create', 'teams.show', 'teams.edit', 'teams.players.*'], 'Squadre', 'users'],
                ['coaches.index', 'coaches.*', 'Allenatori', 'coach'],
                ['games.index', ['games.index', 'games.create', 'games.edit', 'games.sheet.*'], 'Calendario', 'calendar'],
                ['attendances.index', ['attendances.*', 'teams.attendances.*'], 'Presenze', 'checklist'],
                ['callups.index', ['callups.*', 'games.callups.*'], 'Distinte', 'clipboard'],
                ['imports.index', 'imports.*', 'Importa', 'upload'],
            ]
            : [
                ['dashboard', 'dashboard', 'Home', 'home'],
                ['teams.index', ['teams.index', 'teams.show', 'teams.players.*'], 'La mia rosa', 'shirt'],
                ['attendances.index', ['attendances.*', 'teams.attendances.*'], 'Presenze', 'checklist'],
                ['callups.index', ['callups.*', 'games.callups.*'], 'Distinte', 'clipboard'],
            ];
    @endphp
    <body @class(['has-nav', 'nav-wide' => count($navItems) > 6])>
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="{{ route('dashboard') }}">
                <span class="brand-mark"><x-icon name="ball" /></span>
                <span class="brand-name">{{ config('app.name') }}</span>
            </a>
            <nav class="topnav" aria-label="Menu principale">
                @foreach ($navItems as [$route, $pattern, $label, $icon])
                    <a href="{{ route($route) }}" @class(['active' => request()->routeIs(...(array) $pattern)]) @if (request()->routeIs(...(array) $pattern)) aria-current="page" @endif>
                        <x-icon :name="$icon" /> {{ $label }}
                    </a>
                @endforeach
            </nav>
            <div class="user-box">
                <div class="user-chip">
                    <strong>{{ auth()->user()->name }}</strong>
                    <span>{{ auth()->user()->role->label() }}</span>
                </div>
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
