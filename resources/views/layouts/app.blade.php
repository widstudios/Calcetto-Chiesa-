<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Gestionale') · {{ config('app.name') }}</title>
    <style>
        :root { --green: #1f7a3d; --bg: #f4f6f4; --text: #1d2420; --muted: #5d6b62; --line: #dde3de; }
        * { box-sizing: border-box; }
        body { margin: 0; font-family: system-ui, -apple-system, "Segoe UI", sans-serif; background: var(--bg); color: var(--text); }
        header { background: var(--green); color: #fff; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; }
        header a, header button { color: #fff; }
        header button { background: none; border: 1px solid rgba(255,255,255,.6); border-radius: 6px; padding: 4px 10px; cursor: pointer; }
        main { max-width: 960px; margin: 0 auto; padding: 24px 16px; }
        .card { background: #fff; border: 1px solid var(--line); border-radius: 10px; padding: 16px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid var(--line); }
        .muted { color: var(--muted); }
        .badge { font-size: 12px; padding: 2px 8px; border-radius: 999px; background: #e3f1e7; color: var(--green); }
        .badge.bench { background: #eee; color: var(--muted); }
        label { display: block; margin: 12px 0 4px; }
        input[type=email], input[type=password], input[type=text], input[type=number], input[type=date], input[type=datetime-local], select, textarea { width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 6px; font: inherit; background: #fff; }
        .btn { display: inline-block; margin-top: 16px; background: var(--green); color: #fff; border: 0; border-radius: 6px; padding: 10px 16px; cursor: pointer; text-decoration: none; font: inherit; }
        .btn.small { margin-top: 0; padding: 6px 10px; font-size: 14px; }
        .btn.secondary { background: #fff; color: var(--green); border: 1px solid var(--green); }
        .btn.danger { background: #fff; color: #b3261e; border: 1px solid #b3261e; }
        .error { color: #b3261e; font-size: 14px; }
        .status { background: #e3f1e7; color: var(--green); border-radius: 8px; padding: 10px 14px; margin-bottom: 16px; }
        nav.main { background: #fff; border-bottom: 1px solid var(--line); padding: 0 16px; display: flex; flex-wrap: wrap; gap: 0 4px; }
        nav.main a { padding: 12px 10px; color: var(--text); text-decoration: none; white-space: nowrap; border-bottom: 3px solid transparent; }
        nav.main a.active { border-bottom-color: var(--green); color: var(--green); font-weight: 600; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; gap: 12px; flex-wrap: wrap; margin-bottom: 16px; }
        .toolbar h1 { margin: 0; }
        .actions { display: flex; gap: 6px; align-items: center; flex-wrap: wrap; }
        .actions form { margin: 0; }
        .grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 0 16px; }
        .table-wrap { overflow-x: auto; }
    </style>
</head>
<body>
<header>
    <strong>{{ config('app.name') }}</strong>
    @auth
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <span>{{ auth()->user()->name }} ({{ auth()->user()->role->label() }})</span>
            <button type="submit">Esci</button>
        </form>
    @endauth
</header>
@auth
    <nav class="main">
        <a href="{{ route('dashboard') }}" @class(['active' => request()->routeIs('dashboard')])>Home</a>
        @if (auth()->user()->isAdmin())
            <a href="{{ route('tournaments.index') }}" @class(['active' => request()->routeIs('tournaments.*')])>Tornei</a>
            <a href="{{ route('teams.index') }}" @class(['active' => request()->routeIs('teams.*')])>Squadre e rose</a>
            <a href="{{ route('coaches.index') }}" @class(['active' => request()->routeIs('coaches.*')])>Allenatori</a>
            <a href="{{ route('games.index') }}" @class(['active' => request()->routeIs('games.*')])>Calendario</a>
        @else
            <a href="{{ route('teams.index') }}" @class(['active' => request()->routeIs('teams.*')])>La mia rosa</a>
        @endif
    </nav>
@endauth
<main>
    @if (session('status'))
        <div class="status">{{ session('status') }}</div>
    @endif
    @yield('content')
</main>
</body>
</html>
