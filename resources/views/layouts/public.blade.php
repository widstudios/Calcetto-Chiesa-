<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#052e16">
    <title>@yield('title') · {{ config('app.name') }}</title>
    <meta name="description" content="@yield('description', 'Classifica, risultati, marcatori e pagelle del torneo.')">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Barlow+Condensed:wght@600;700&family=Barlow:wght@400;500;600;700&display=swap">
    <link rel="stylesheet" href="{{ asset('css/app.css') }}?v={{ @filemtime(public_path('css/app.css')) }}">
</head>
<body class="public">
    <header class="topbar">
        <div class="topbar-inner">
            <a class="brand" href="{{ route('home') }}">
                <span class="brand-mark"><x-icon name="ball" /></span>
                <span class="brand-name">{{ config('app.name') }}</span>
            </a>
            <div class="user-box">
                @auth
                    <a class="btn small accent" href="{{ route('dashboard') }}">Gestionale</a>
                @else
                    <a class="btn small accent" href="{{ route('login') }}">Area riservata</a>
                @endauth
            </div>
        </div>
    </header>
    @yield('hero')
    <main>
        @yield('content')
    </main>
    <footer class="site-footer">{{ config('app.name') }} · {{ now()->year }}</footer>
</body>
</html>
