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
        input[type=email], input[type=password] { width: 100%; padding: 8px; border: 1px solid var(--line); border-radius: 6px; }
        .btn { margin-top: 16px; background: var(--green); color: #fff; border: 0; border-radius: 6px; padding: 10px 16px; cursor: pointer; }
        .error { color: #b3261e; font-size: 14px; }
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
<main>
    @yield('content')
</main>
</body>
</html>
